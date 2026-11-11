<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\PlatformSettings;
use App\Services\WorkflowActivity;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function tokenResponse(User $user, int $status = 200)
    {
        $token = $user->createToken('ccp-browser', ['*'], now()->addHours(8));

        return (new UserResource($user))->additional(['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $token->accessToken->expires_at])->response()->setStatusCode($status);
    }

    public function register(AuthRequest $r)
    {
        $u = DB::transaction(function () use ($r) {
            $role = $r->input('role', 'student');
            $data = $r->safe()->except(['password_confirmation', 'company_name']);
            $data['role'] = $role;
            $data['status'] = $role === 'student' ? 'active' : 'pending';
            if ($role === 'recruiter') {
                $data['company_id'] = Company::create(['name' => $r->company_name, 'verified' => false])->id;
            }
            $u = User::create($data);
            if ($role === 'student') {
                StudentProfile::create(['user_id' => $u->id, 'department' => $u->department]);
            }
            if ($role === 'advisor') {
                DB::table('advisor_profiles')->insert(['user_id' => $u->id, 'capacity' => PlatformSettings::get('default_capacity', 20), 'is_accepting' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($role !== 'student') {
                WorkflowActivity::notify(User::where('role', 'admin')->where('status', 'active')->pluck('id')->all(), 'New '.$role.' account awaiting approval', $u->name, 'general', '/users');
            }

            return $u;
        });
        if (config('ccp.require_email_verification')) {
            event(new Registered($u));
        }

        return $this->tokenResponse($u, 201);
    }

    public function login(AuthRequest $r)
    {
        $u = User::where('email', $r->email)->first();
        if (! $u || ! Hash::check($r->password, $u->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid email or password.']);
        }
        if ($u->role->value !== $r->role) {
            throw ValidationException::withMessages(['role' => 'Selected workspace does not match this account.']);
        }
        abort_unless(in_array($u->status, ['active', 'pending']), 403, 'This account is not active. Contact your administrator.');
        $u->update(['last_login_at' => now()]);

        return $this->tokenResponse($u);
    }

    public function me(Request $r)
    {
        return new UserResource($r->user());
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function resend(Request $r)
    {
        if (! config('ccp.require_email_verification')) {
            return ['message' => 'Email verification is currently disabled.'];
        }
        if (! $r->user()->hasVerifiedEmail()) {
            $r->user()->sendEmailVerificationNotification();
        }

        return ['message' => 'Verification link sent.'];
    }

    public function verify(EmailVerificationRequest $r)
    {
        $r->fulfill();

        return ['message' => 'Email verified.'];
    }

    public function forgot(AuthRequest $r)
    {
        Password::sendResetLink($r->only('email'));

        return ['message' => 'If the account exists, a reset link has been sent.'];
    }

    public function reset(AuthRequest $r)
    {
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $u, string $password) {
            $u->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'must_change_password' => false])->save();
            $u->tokens()->delete();
            event(new PasswordReset($u));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return ['message' => __($status)];
    }

    public function changePassword(AuthRequest $r)
    {
        $r->user()->update(['password' => $r->password, 'must_change_password' => false]);
        $currentId = $r->user()->currentAccessToken()?->id;
        $r->user()->tokens()->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))->delete();

        return new UserResource($r->user());
    }
}

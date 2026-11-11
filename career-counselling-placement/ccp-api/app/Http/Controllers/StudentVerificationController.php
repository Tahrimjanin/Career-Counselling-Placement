<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\VerificationRequest;
use App\Http\Resources\ProfileResource;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\WorkflowActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentVerificationController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('viewAnyVerification', User::class);
        $d = $r->validate(['status' => 'nullable|in:pending,verified,rejected', 'page' => 'nullable|integer|min:1']);

        return ProfileResource::collection(User::where('role', Role::Student)->with('profile')->when($d['status'] ?? null, fn ($q, $v) => $q->whereHas('profile', fn ($p) => $p->where('verification_status', $v)))->orderBy('id')->paginate(15));
    }

    public function show(User $user)
    {
        $this->authorize('viewVerification', $user);

        return new ProfileResource($user->load('profile'));
    }

    public function update(VerificationRequest $r, User $user)
    {
        $this->authorize('verify', $user);
        DB::transaction(function () use ($r, $user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless(! config('ccp.require_email_verification') || $user->hasVerifiedEmail(), 422, 'The student must verify their email first.');
            $p = StudentProfile::firstOrCreate(['user_id' => $user->id]);
            $old = $p->only(['verification_status', 'verification_reason']);
            WorkflowActivity::notify([$user->id], 'Student verification: '.$r->status, $r->reason, 'general', '/profile');
            $p->update(['verification_status' => $r->status, 'verification_reason' => $r->reason, 'verified_by' => $r->user()->id, 'verified_at' => now()]);
            DB::table('audit_logs')->insert(['actor_id' => $r->user()->id, 'action' => 'student.verification', 'entity_type' => 'student_profile', 'entity_id' => $p->id, 'old_values' => json_encode($old), 'new_values' => json_encode($p->only(['verification_status', 'verification_reason'])), 'ip' => $r->ip(), 'user_agent' => $r->userAgent(), 'created_at' => now(), 'updated_at' => now()]);
        });

        return new ProfileResource($user->load('profile'));
    }
}

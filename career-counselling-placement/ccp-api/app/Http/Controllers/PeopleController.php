<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\PlatformSettings;
use App\Services\WorkflowActivity;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeopleController extends Controller
{
    public function students(Request $r)
    {
        return User::whereIn('id', DB::table('advisor_assignments')->where('advisor_id', $r->user()->id)->where('status', 'active')->select('student_id'))->get(['id', 'name', 'email', 'department']);
    }

    public function users(Request $r)
    {
        return User::with('advisor:id,name')->when($r->role, fn ($q, $v) => $q->where('role', $v))->orderBy('role')->get();
    }

    public function createUser(CreateUserRequest $r)
    {
        $this->authorize('create', User::class);
        $u = DB::transaction(function () use ($r) {
            $u = User::create($r->safe()->except('password_confirmation') + ['must_change_password' => true]);
            if ($u->role === Role::Student) {
                StudentProfile::create(['user_id' => $u->id]);
            }
            if ($u->role === Role::Advisor) {
                DB::table('advisor_profiles')->insert(['user_id' => $u->id, 'capacity' => PlatformSettings::get('default_capacity', 20), 'is_accepting' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            WorkflowActivity::audit($r->user()->id, 'user.created', 'user', $u->id, ['role' => $u->role->value]);

            return $u;
        });
        if (config('ccp.require_email_verification')) {
            event(new Registered($u));
        }

        return (new UserResource($u))->response()->setStatusCode(201);
    }

    public function companies()
    {
        return Company::withCount('opportunities')->get();
    }

    public function verify(Company $company)
    {
        $this->authorize('create', User::class);
        $company->update(['verified' => ! $company->verified]);
        WorkflowActivity::audit(request()->user()->id, 'company.verification', 'company', $company->id, ['verified' => $company->verified]);
        WorkflowActivity::notify(User::where('company_id', $company->id)->pluck('id')->all(), 'Company verification updated', $company->verified ? 'Your company is verified. You can submit opportunities.' : 'Company verification has been removed.', 'general', '/post');

        return $company;
    }
}

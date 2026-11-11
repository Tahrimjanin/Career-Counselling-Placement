<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ProfileStepRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProfileCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $r)
    {
        $this->authorize('updateProfile', $r->user());

        return new ProfileResource($r->user()->load('profile'));
    }

    public function update(ProfileStepRequest $r, int $step)
    {
        $this->authorize('updateProfile', $r->user());
        $u = DB::transaction(function () use ($r, $step) {
            $u = User::lockForUpdate()->findOrFail($r->user()->id);
            $d = $r->safe()->except('advance');
            if ($step === 1) {
                $u->update(collect($d)->only(['name', 'phone'])->all());
                if ($u->role === Role::Recruiter && isset($d['company'])) {
                    $company = Company::lockForUpdate()->findOrFail($u->company_id);
                    $this->authorize('updateProfile', $company);
                    $company->fill($d['company']);
                    if ($company->isDirty('name')) {
                        $company->verified = false;
                    }
                    $company->save();
                }
            }
            if ($u->role !== Role::Student) {
                return $u->load('profile');
            }
            $p = StudentProfile::firstOrCreate(['user_id' => $u->id]);
            if (in_array($step, [2, 7])) {
                $p->fill($d);
                if ($step === 2 && isset($d['department'])) {
                    $u->update(['department' => $d['department']]);
                }
            }
            if (in_array($step, [3, 4]) && array_key_exists('skills', $d)) {
                $type = $step === 3 ? 'technical' : 'soft';
                $ids = DB::table('skills')->where('type', $type)->pluck('id');
                DB::table('student_skill')->where('user_id', $u->id)->whereIn('skill_id', $ids)->delete();
                foreach ($d['skills'] as $name) {
                    $name = trim($name);
                    $existing = DB::table('skills')->where('name', $name)->first();
                    if ($existing && $existing->type !== $type) {
                        throw ValidationException::withMessages(['skills' => 'A skill with that name already belongs to the other category.']);
                    }
                    DB::table('skills')->insertOrIgnore(['name' => $name, 'type' => $type, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('student_skill')->insertOrIgnore(['user_id' => $u->id, 'skill_id' => DB::table('skills')->where('name', $name)->value('id'), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            if (in_array($step, [5, 6])) {
                $flag = $step === 5 ? 'has_experience' : 'has_certificates';
                $table = $step === 5 ? 'profile_experiences' : 'profile_certificates';
                if (array_key_exists($flag, $d)) {
                    $p->$flag = $d[$flag];
                }
                if (array_key_exists('entries', $d) || $p->$flag === false) {
                    DB::table($table)->where('user_id', $u->id)->delete();
                    if ($p->$flag !== false) {
                        foreach ($d['entries'] ?? [] as $entry) {
                            DB::table($table)->insert(collect($entry)->only(['title', 'org', 'start_date', 'end_date', 'description'])->all() + ['user_id' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
                        }
                    }
                }
            }
            // Academic/contact changes require a fresh university review.
            if ((($step === 1 && $u->wasChanged(['name', 'phone'])) || ($step === 2 && $p->isDirty())) && $p->verification_status !== 'pending') {
                $p->verification_status = 'pending';
                $p->verified_by = null;
                $p->verified_at = null;
                $p->verification_reason = null;
            }
            $p->wizard_step = $r->boolean('advance') ? min(8, $step + 1) : $step;
            $p->save();
            $u->load('profile');
            $p->completion_percent = app(ProfileCompletion::class)->calculate($u)['percent'];
            $p->save();

            return $u->load('profile');
        });

        return new ProfileResource($u);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (DB::table('users')->orderBy('id')->get() as $u) {
                if ($u->role === 'student') {
                    DB::table('student_profiles')->updateOrInsert(['user_id' => $u->id], ['department' => $u->department, 'created_at' => now(), 'updated_at' => now()]);
                    if ($u->advisor_id && DB::table('users')->where('id', $u->advisor_id)->where('role', 'advisor')->exists()) {
                        DB::table('advisor_assignments')->updateOrInsert(['student_id' => $u->id, 'status' => 'active'], ['advisor_id' => $u->advisor_id, 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                    }
                    foreach (json_decode($u->skills ?? '[]', true) ?: [] as $name) {
                        if (! is_string($name) || trim($name) === '') {
                            continue;
                        }
                        DB::table('skills')->insertOrIgnore(['name' => trim($name), 'type' => 'technical', 'created_at' => now(), 'updated_at' => now()]);
                        DB::table('student_skill')->insertOrIgnore(['user_id' => $u->id, 'skill_id' => DB::table('skills')->where('name', trim($name))->value('id'), 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
                if ($u->role === 'advisor') {
                    DB::table('advisor_profiles')->updateOrInsert(['user_id' => $u->id], ['created_at' => now(), 'updated_at' => now()]);
                }
            }
            foreach (DB::table('cvs')->whereNull('file_path')->where('path', '!=', '')->get() as $cv) {
                DB::table('cvs')->where('id', $cv->id)->update(['file_path' => $cv->path, 'source' => 'uploaded']);
            }
            foreach (DB::table('applications')->get() as $app) {
                $status = $app->admin_review === 'rejected' ? 'rejected_by_university' : ($app->forwarded_at ? match ($app->status) {
                    'Shortlisted' => 'shortlisted','Interview' => 'interview','Offer' => 'offer','Rejected' => 'rejected_by_company',default => 'forwarded'
                } : 'submitted');
                DB::table('applications')->where('id', $app->id)->update(['student_id' => $app->user_id, 'workflow_status' => $status, 'submitted_at' => $app->created_at, 'admin_decision' => $app->admin_review]);
            }
            foreach (DB::table('opportunities')->get() as $o) {
                foreach (json_decode($o->skills ?? '[]', true) ?: [] as $name) {
                    if (! is_string($name) || trim($name) === '') {
                        continue;
                    }
                    DB::table('skills')->insertOrIgnore(['name' => trim($name), 'type' => 'technical', 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('opportunity_skill')->insertOrIgnore(['opportunity_id' => $o->id, 'skill_id' => DB::table('skills')->where('name', trim($name))->value('id'), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            foreach (DB::table('notes')->join('users', 'users.id', '=', 'notes.user_id')->where('users.role', 'advisor')->select('notes.*')->get() as $n) {
                DB::table('advisor_notes')->updateOrInsert(['id' => $n->id], ['advisor_id' => $n->user_id, 'student_id' => $n->student_id, 'body' => $n->body, 'is_private' => $n->private, 'created_at' => $n->created_at, 'updated_at' => $n->updated_at]);
            }
        });

    }

    public function down(): void
    {
        // Legacy columns and records are deliberately retained; earlier schema downs remove only new tables/columns.
    }
};

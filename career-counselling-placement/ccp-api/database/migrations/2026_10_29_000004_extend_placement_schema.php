<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fail before adding a constraint rather than silently renumbering referenced CV versions.
        if (DB::table('cvs')->select('user_id', 'version')->groupBy('user_id', 'version')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate legacy CV versions: resolve from backup before migrating.');
        }

        Schema::table('cvs', function (Blueprint $t) {
            $t->string('title')->nullable();
            $t->string('source')->default('uploaded');
            $t->json('content')->nullable();
            $t->string('file_path')->nullable();
            $t->string('mime')->nullable();
            $t->unsignedInteger('size')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->softDeletes();
            $t->index('status');
        });
        Schema::table('cvs', fn (Blueprint $t) => $t->unique(['user_id', 'version']));
        Schema::table('opportunities', function (Blueprint $t) {
            $t->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('work_mode')->default('On-site')->index();
            $t->boolean('is_paid')->default(false);
            $t->unsignedInteger('stipend_min')->nullable();
            $t->unsignedInteger('stipend_max')->nullable();
            $t->string('stipend_label')->nullable();
            $t->text('responsibilities')->nullable();
            $t->text('requirements')->nullable();
            $t->decimal('min_cgpa', 3, 2)->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('rejection_reason')->nullable();
            $t->softDeletes();
            $t->index(['status', 'deadline']);
            $t->index('type');
            $t->index('location');
        });
        if (DB::getDriverName() === 'mysql') {
            Schema::table('opportunities', fn (Blueprint $t) => $t->fullText(['title', 'description']));
        }
        Schema::table('applications', function (Blueprint $t) {
            $t->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
            $t->json('eligibility_snapshot')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->string('workflow_status')->nullable()->index();
            $t->string('admin_decision')->nullable();
            $t->text('admin_reason')->nullable();
            $t->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('admin_reviewed_at')->nullable();
            $t->foreignId('forwarded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('withdrawn_at')->nullable();
            $t->index('status');
            $t->unique(['student_id', 'opportunity_id']);
        });
        Schema::table('interviews', function (Blueprint $t) {
            $t->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedSmallInteger('duration_min')->default(30);
            $t->string('type')->default('Technical');
            $t->string('interviewer')->nullable();
            $t->string('link_or_location')->nullable();
            $t->text('prep_notes')->nullable();
            $t->string('status')->default('scheduled')->index();
            $t->index('scheduled_at');
        });
        Schema::create('cv_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cv_id')->constrained('cvs')->cascadeOnDelete();
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->string('decision');
            $t->text('strengths');
            $t->text('improvements');
            $t->string('placement_recommendation')->nullable();
            $t->timestamps();
        });
        Schema::create('opportunity_skill', function (Blueprint $t) {
            $t->id();
            $t->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $t->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $t->unique(['opportunity_id', 'skill_id']);
            $t->timestamps();
        });
        Schema::create('saved_opportunities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $t->unique(['user_id', 'opportunity_id']);
            $t->timestamps();
        });
        Schema::create('application_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('interview_feedback', function (Blueprint $t) {
            $t->id();
            $t->foreignId('interview_id')->constrained('interviews')->cascadeOnDelete();
            $t->unique('interview_id');
            $t->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->string('recommendation');
            $t->text('comments');
            $t->timestamps();
        });
        Schema::create('offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->unique('application_id');
            $t->text('details');
            $t->timestamp('expires_at')->index();
            $t->string('status')->default('pending')->index();
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
        });
        Schema::create('placement_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->unique('application_id');
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $t->string('role_title');
            $t->string('job_type');
            $t->date('start_date')->index();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('placement_records');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('interview_feedback');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('saved_opportunities');
        Schema::dropIfExists('opportunity_skill');
        Schema::dropIfExists('cv_reviews');
        Schema::table('interviews', function (Blueprint $t) {
            $t->dropIndex(['status']);
            $t->dropConstrainedForeignId('scheduled_by');
            $t->dropIndex(['scheduled_at']);
            $t->dropColumn(['duration_min', 'type', 'interviewer', 'link_or_location', 'prep_notes', 'status']);
        });
        Schema::table('applications', function (Blueprint $t) {
            $t->dropIndex(['workflow_status']);
            $t->dropForeign(['student_id']);
            $t->dropUnique(['student_id', 'opportunity_id']);
            $t->dropColumn('student_id');
            $t->dropIndex(['status']);
            foreach (['admin_reviewed_by', 'forwarded_by'] as $c) {
                $t->dropConstrainedForeignId($c);
            } $t->dropColumn(['eligibility_snapshot', 'submitted_at', 'workflow_status', 'admin_decision', 'admin_reason', 'admin_reviewed_at', 'withdrawn_at']);
        });
        if (DB::getDriverName() === 'mysql') {
            Schema::table('opportunities', fn (Blueprint $t) => $t->dropFullText(['title', 'description']));
        }
        Schema::table('opportunities', function (Blueprint $t) {
            $t->dropIndex(['work_mode']);
            $t->dropIndex(['status', 'deadline']);
            $t->dropIndex(['type']);
            $t->dropIndex(['location']);
            foreach (['posted_by', 'approved_by'] as $c) {
                $t->dropConstrainedForeignId($c);
            } $t->dropColumn(['work_mode', 'is_paid', 'stipend_min', 'stipend_max', 'stipend_label', 'responsibilities', 'requirements', 'min_cgpa', 'rejection_reason', 'deleted_at']);
        });
        if (DB::getDriverName() === 'mysql' && ! Schema::hasIndex('cvs', 'cvs_user_id_foreign')) {
            Schema::table('cvs', fn (Blueprint $t) => $t->index('user_id', 'cvs_user_id_foreign'));
        }
        Schema::table('cvs', function (Blueprint $t) {
            $t->dropUnique(['user_id', 'version']);
            $t->dropIndex(['status']);
            $t->dropConstrainedForeignId('reviewed_by');
            $t->dropColumn(['title', 'source', 'content', 'file_path', 'mime', 'size', 'submitted_at', 'reviewed_at', 'deleted_at']);
        });

    }
};

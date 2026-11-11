<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unique('user_id');
            $t->string('title')->nullable();
            $t->text('bio')->nullable();
            $t->unsignedInteger('capacity')->default(20);
            $t->boolean('is_accepting')->default(true);
            $t->timestamps();
        });
        Schema::create('advisor_expertise', function (Blueprint $t) {
            $t->id();
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->string('tag');
            $t->unique(['advisor_id', 'tag']);
            $t->timestamps();
        });
        Schema::create('advisor_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('rank');
            $t->string('status')->default('pending')->index();
            $t->unique(['student_id', 'rank']);
            $t->unique(['student_id', 'advisor_id']);
            $t->timestamps();
        });
        Schema::create('advisor_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status')->default('active')->index();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->unsignedBigInteger('active_student_id')->nullable()->storedAs("CASE WHEN status = 'active' THEN student_id ELSE NULL END");
            $t->unique('active_student_id');
            $t->timestamps();
        });
        Schema::create('counselling_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->string('type');
            $t->dateTime('scheduled_at')->index();
            $t->dateTime('ends_at');
            $t->string('mode');
            $t->string('location_or_link')->nullable();
            $t->string('status')->default('requested')->index();
            $t->text('student_note')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('cancel_reason')->nullable();
            $t->index(['advisor_id', 'scheduled_at']);
            $t->timestamps();
        });
        Schema::create('session_feedback', function (Blueprint $t) {
            $t->id();
            $t->foreignId('session_id')->constrained('counselling_sessions')->cascadeOnDelete();
            $t->unique('session_id');
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->text('next_step');
            $t->timestamps();
        });
        Schema::create('career_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->unique('student_id');
            $t->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $t->string('target_role');
            $t->text('strengths')->nullable();
            $t->text('skill_gaps')->nullable();
            $t->text('advisor_comment')->nullable();
            $t->timestamps();
        });
        Schema::create('plan_milestones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plan_id')->constrained('career_plans')->cascadeOnDelete();
            $t->string('title');
            $t->string('status')->default('not_started')->index();
            $t->date('due_date')->nullable()->index();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('plan_milestones');
        Schema::dropIfExists('career_plans');
        Schema::dropIfExists('session_feedback');
        Schema::dropIfExists('counselling_sessions');
        Schema::dropIfExists('advisor_assignments');
        Schema::dropIfExists('advisor_preferences');
        Schema::dropIfExists('advisor_expertise');
        Schema::dropIfExists('advisor_profiles');
    }
};

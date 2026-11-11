<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unique('user_id');
            $t->string('student_no')->nullable()->unique();
            $t->string('department')->nullable()->index();
            $t->string('batch')->nullable();
            $t->decimal('cgpa', 3, 2)->nullable();
            $t->string('ssc_institution')->nullable();
            $t->string('ssc_board')->nullable();
            $t->string('hsc_institution')->nullable();
            $t->string('hsc_board')->nullable();
            $t->unsignedSmallInteger('ssc_year')->nullable();
            $t->unsignedSmallInteger('hsc_year')->nullable();
            $t->decimal('ssc_gpa', 3, 2)->nullable();
            $t->decimal('hsc_gpa', 3, 2)->nullable();
            $t->text('career_objective')->nullable();
            $t->json('interests')->nullable();
            $t->boolean('has_experience')->nullable();
            $t->boolean('has_certificates')->nullable();
            $t->unsignedTinyInteger('completion_percent')->default(0);
            $t->unsignedTinyInteger('wizard_step')->default(1);
            $t->string('verification_status')->default('pending')->index();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->text('verification_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('profile_experiences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->string('org');
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('profile_certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->string('org');
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('skills', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('type')->index();
            $t->timestamps();
        });
        Schema::create('student_skill', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $t->string('level')->default('beginner');
            $t->unique(['user_id', 'skill_id']);
            $t->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('student_skill');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('profile_certificates');
        Schema::dropIfExists('profile_experiences');
        Schema::dropIfExists('student_profiles');
    }
};

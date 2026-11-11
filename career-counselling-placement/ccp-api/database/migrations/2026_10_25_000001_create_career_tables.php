<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('website')->nullable();
            $t->boolean('verified')->default(false);
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('student')->index(); // student|advisor|admin|recruiter
            $t->foreignId('advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $t->string('department')->nullable();
            $t->json('skills')->nullable();
        });
        Schema::create('opportunities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('type')->default('Internship');
            $t->string('location');
            $t->text('description');
            $t->json('skills')->nullable();
            $t->date('deadline')->nullable();
            $t->string('status')->default('open');
            $t->timestamps();
        });
        Schema::create('cvs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->string('status')->default('pending'); // pending|approved|changes_requested
            $t->text('feedback')->nullable();
            $t->timestamps();
        });
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('cv_id')->constrained('cvs');
            $t->string('status')->default('Applied'); // Applied|Shortlisted|Interview|Offer|Rejected
            $t->string('admin_review')->default('pending'); // pending|approved|rejected
            $t->timestamp('forwarded_at')->nullable();
            $t->text('cover_note')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'opportunity_id']);
        });
        Schema::create('interviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $t->dateTime('scheduled_at');
            $t->string('mode')->default('Video call');
            $t->string('link')->nullable();
            $t->timestamps();
        });
        Schema::create('notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->boolean('private')->default(false);
            $t->timestamps();
        });
        Schema::create('alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->boolean('read')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['alerts', 'notes', 'interviews', 'applications', 'cvs', 'opportunities'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('advisor_id');
            $t->dropConstrainedForeignId('company_id');
            $t->dropIndex(['role']);
            $t->dropColumn(['role', 'department', 'skills']);
        });
        Schema::dropIfExists('companies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->whereNotIn('role', ['student', 'advisor', 'admin', 'recruiter'])->exists()) {
            throw new RuntimeException('Unknown legacy role: correct it from the source records before migrating.');
        }
        Schema::table('users', function (Blueprint $t) {
            $t->string('status')->default('active')->index();
            $t->string('phone', 30)->nullable();
            $t->string('avatar_path')->nullable();
            $t->boolean('must_change_password')->default(false);
            $t->timestamp('last_login_at')->nullable();
            $t->softDeletes();
        });
        Schema::table('companies', function (Blueprint $t) {
            $t->string('industry')->nullable();
            $t->text('description')->nullable();
            $t->string('logo_path')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
        });

    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $t) {
            $t->dropConstrainedForeignId('verified_by');
            $t->dropColumn(['industry', 'description', 'logo_path', 'verified_at']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['status']);
            $t->dropColumn(['status', 'phone', 'avatar_path', 'must_change_password', 'last_login_at', 'deleted_at']);
        });
    }
};

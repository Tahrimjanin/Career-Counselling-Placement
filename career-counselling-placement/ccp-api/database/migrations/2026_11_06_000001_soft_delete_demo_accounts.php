<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('users')
            ->whereNull('deleted_at')
            ->where('email', 'like', '%@demo.test')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->whereIn('tokenable_id', $ids)
                ->delete();

            DB::table('users')
                ->whereIn('id', $ids)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    public function down(): void {}
};

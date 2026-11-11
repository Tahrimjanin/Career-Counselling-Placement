<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $t) {
            $t->string('title')->nullable();
            $t->unsignedSmallInteger('interval_days')->nullable();
        });
        Schema::create('notification_events', function (Blueprint $t) {
            $t->id();
            $t->string('event_key')->unique();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
        Schema::table('reminders', fn (Blueprint $t) => $t->dropColumn(['title', 'interval_days']));
    }
};

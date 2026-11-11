<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PlatformMail;
use App\Notifications\PlatformNotice;
use Illuminate\Support\Facades\DB;

class WorkflowActivity
{
    public static function notify(array $users, string $title, ?string $body = null, string $category = 'general', string $url = '/', ?string $key = null): void
    {
        foreach (array_unique(array_filter($users)) as $id) {
            DB::transaction(function () use ($id, $title, $body, $category, $url, $key) {
                $user = User::find($id);
                if (! $user || $user->status === 'suspended') {
                    return;
                }
                if ($key && ! DB::table('notification_events')->insertOrIgnore(['event_key' => $key.':'.$id, 'created_at' => now(), 'updated_at' => now()])) {
                    return;
                }
                DB::table('alerts')->insert(['user_id' => $id, 'title' => $title, 'body' => $body, 'read' => false, 'created_at' => now(), 'updated_at' => now()]);
                $payload = compact('title', 'body', 'category', 'url');
                $user->notifyNow(new PlatformNotice($payload));
                $user->notify(new PlatformMail($payload));
            });
        }
    }

    public static function audit(int $actor, string $action, string $type, int $id, array $data = []): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $actor, 'action' => $action, 'entity_type' => $type, 'entity_id' => $id, 'new_values' => json_encode($data), 'ip' => app()->runningInConsole() ? null : request()->ip(), 'user_agent' => app()->runningInConsole() ? null : substr(request()->userAgent() ?? '', 0, 1000), 'created_at' => now(), 'updated_at' => now()]);
    }
}

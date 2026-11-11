<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        $d = $r->validate(['filter' => 'nullable|in:all,unread,deadlines,interviews', 'page' => 'integer|min:1', 'per_page' => 'integer|min:1|max:50']);
        $q = $r->user()->notifications();
        if ($r->filter === 'unread') {
            $q->whereNull('read_at');
        }if (in_array($r->filter, ['deadlines', 'interviews'])) {
            $q->where('data->category', $r->filter);
        }

return ['data' => $q->latest()->paginate($r->integer('per_page', 20)), 'unread' => $r->user()->unreadNotifications()->count()];
    }

    public function read(Request $r, ?string $notice = null)
    {
        $q = $r->user()->notifications();
        if ($notice) {
            $n = $q->findOrFail($notice);
            $n->markAsRead();
        } else {
            $q->whereNull('read_at')->update(['read_at' => now()]);
            DB::table('alerts')->where('user_id', $r->user()->id)->update(['read' => true]);
        }

return ['message' => 'Marked as read.'];
    }

    public function reminders(Request $r)
    {
        return ['data' => DB::table('reminders')->where('user_id', $r->user()->id)->orderBy('remind_at')->get()];
    }

    public function saveReminder(Request $r)
    {
        $d = $r->validate(['application_id' => 'required|integer', 'interval_days' => 'required|integer|min:1|max:90']);
        $a = Application::findOrFail($d['application_id']);
        abort_unless($r->user()->role->value === 'student' && $a->user_id === $r->user()->id, 403);
        DB::transaction(function () use ($r, $a, $d) {
            User::whereKey($r->user()->id)->lockForUpdate()->first();
            DB::table('reminders')->updateOrInsert(['user_id' => $r->user()->id, 'type' => 'followup', 'remindable_type' => 'application', 'remindable_id' => $a->id], ['title' => 'Follow up: '.$a->opportunity->title, 'interval_days' => $d['interval_days'], 'remind_at' => now()->addDays($d['interval_days']), 'sent_at' => null, 'created_at' => now(), 'updated_at' => now()]);
        });

        return ['message' => 'Follow-up reminder scheduled.'];
    }

    public function deleteReminder(Request $r, int $id)
    {
        $n = DB::table('reminders')->where('id', $id)->where('user_id', $r->user()->id)->first();
        abort_unless($n, 404);
        DB::table('reminders')->where('id', $id)->delete();

        return response()->noContent();
    }
}

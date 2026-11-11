<?php

namespace App\Services;

use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderScheduler
{
    public function run(): void
    {
        foreach (DB::table('counselling_sessions')->where('status', 'confirmed')->whereBetween('scheduled_at', [now(), now()->addDay()])->get() as $s) {
            $hours = Carbon::parse($s->scheduled_at)->diffInMinutes(now(), true) <= 60 ? 1 : 24;
            WorkflowActivity::notify([$s->student_id, $s->advisor_id], 'Counselling session reminder', 'Your session starts at '.Carbon::parse($s->scheduled_at)->timezone('Asia/Dhaka')->format('d M Y H:i').' (Dhaka).', 'general', '/sessions', 'session:'.$s->id.':'.$s->scheduled_at.':'.$hours);
        }
        foreach (DB::table('interviews')->where('status', 'scheduled')->whereBetween('scheduled_at', [now(), now()->addDay()])->get() as $i) {
            $a = DB::table('applications')->find($i->application_id);
            $hours = Carbon::parse($i->scheduled_at)->diffInMinutes(now(), true) <= 60 ? 1 : 24;
            WorkflowActivity::notify([$a->user_id, $i->scheduled_by], 'Interview reminder', 'Interview at '.Carbon::parse($i->scheduled_at)->timezone('Asia/Dhaka')->format('d M Y H:i').' (Dhaka).', 'interviews', '/interviews', 'interview:'.$i->id.':'.$i->scheduled_at.':'.$hours);
        }
        foreach ([3, 1] as $days) {
            foreach (Opportunity::where('status', 'open')->whereDate('deadline', now('Asia/Dhaka')->addDays($days)->toDateString())->get() as $o) {
                $users = DB::table('saved_opportunities')->where('opportunity_id', $o->id)->pluck('user_id')->merge(DB::table('applications')->where('opportunity_id', $o->id)->pluck('user_id'))->all();
                WorkflowActivity::notify($users, 'Opportunity deadline in '.$days.' day(s)', $o->title, 'deadlines', '/opportunities', 'deadline:'.$o->id.':'.$o->deadline.':'.$days);
            }
        }
        foreach (DB::table('reminders')->where('remind_at', '<=', now())->whereNull('sent_at')->pluck('id') as $id) {
            DB::transaction(function () use ($id) {
                $n = DB::table('reminders')->where('id', $id)->lockForUpdate()->first();
                if (! $n || $n->sent_at || Carbon::parse($n->remind_at)->isFuture()) {
                    return;
                }$a = DB::table('applications')->find($n->remindable_id);
                if (! $a || in_array($a->workflow_status, ['offer_accepted', 'offer_declined', 'withdrawn', 'rejected_by_university', 'rejected_by_company'])) {
                    DB::table('reminders')->where('id', $id)->update(['sent_at' => now()]);

                    return;
                }WorkflowActivity::notify([$n->user_id], $n->title ?: 'Application follow-up', 'Check your application and plan the next follow-up.', 'general', '/applications', 'followup:'.$id.':'.$n->remind_at);
                DB::table('reminders')->where('id', $id)->update($n->interval_days ? ['remind_at' => now()->addDays($n->interval_days), 'updated_at' => now()] : ['sent_at' => now(), 'updated_at' => now()]);
            });
        }
    }
}

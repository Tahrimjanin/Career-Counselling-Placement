<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Application;
use App\Models\Company;
use App\Models\Cv;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $r)
    {
        $u = $r->user();
        $data = match ($u->role->value) {
            'student' => $this->student($u), 'advisor' => $this->advisor($u),
            'admin' => $this->admin(), 'recruiter' => $this->recruiter($u),
        };
        $data['alerts'] = Alert::where('user_id', $u->id)->latest()->limit(6)->get();

        return $data;
    }

    private function student($u)
    {
        $pipeline = Application::where('user_id', $u->id)->whereHas('user')->selectRaw('workflow_status, count(*) as c')->groupBy('workflow_status')->pluck('c', 'workflow_status');
        $cv = Cv::where('user_id', $u->id)->latest('version')->first();
        $upcoming = Interview::with('application.opportunity.company:id,name')
            ->whereHas('application', fn ($q) => $q->where('user_id', $u->id)->whereHas('user'))
            ->where('status', 'scheduled')->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->first();

        return ['stats' => [
            ['label' => 'Active Applications', 'value' => $pipeline->except(['rejected_by_company', 'rejected_by_university', 'withdrawn', 'offer_declined', 'offer_accepted'])->sum()],
            ['label' => 'Interviews', 'value' => $pipeline['interview'] ?? 0],
            ['label' => 'Offers', 'value' => $pipeline['offer'] ?? 0],
            ['label' => 'Latest CV', 'value' => $cv ? str_replace('_', ' ', $cv->status) : 'none'],
        ], 'pipeline' => $pipeline, 'upcoming' => $upcoming];
    }

    private function advisor($u)
    {
        return ['stats' => [
            ['label' => 'Assigned Students', 'value' => User::where('advisor_id', $u->id)->count()],
            ['label' => 'CVs Awaiting Review', 'value' => Cv::whereIn('status', ['pending', 'submitted'])->whereHas('student', fn ($q) => $q->where('advisor_id', $u->id))->count()],
        ]];
    }

    private function admin()
    {
        return ['stats' => [
            ['label' => 'Students', 'value' => User::where('role', 'student')->count()],
            ['label' => 'Pending Reviews', 'value' => Application::whereHas('user')->where('admin_review', 'pending')->count()],
            ['label' => 'Verified Companies', 'value' => Company::where('verified', true)->count()],
            ['label' => 'Offers', 'value' => Application::whereHas('user')->where('workflow_status', 'offer')->count()],
        ]];
    }

    private function recruiter($u)
    {
        $c = fn () => Application::forCompany($u->company_id)->whereHas('user');

        return ['stats' => [
            ['label' => 'Candidates', 'value' => $c()->count()],
            ['label' => 'Interviews', 'value' => $c()->where('workflow_status', 'interview')->count()],
            ['label' => 'Offers', 'value' => $c()->where('workflow_status', 'offer')->count()],
        ]];
    }
}

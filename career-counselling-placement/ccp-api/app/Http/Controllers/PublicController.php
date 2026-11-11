<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\PlatformSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    private function publicSettings(): array
    {
        $settings = PlatformSettings::public();
        $logoPath = PlatformSettings::get('brand_logo_path');
        $settings['logo_url'] = $logoPath && Storage::disk('local')->exists($logoPath)
            ? url('/api/public/site-logo').'?v='.substr(hash('sha256', $logoPath), 0, 12)
            : null;

        return $settings;
    }

    public function settings()
    {
        return ['data' => ['settings' => $this->publicSettings()]];
    }

    public function index()
    {
        return ['data' => ['stats' => ['students' => User::where('role', 'student')->where('status', 'active')->count(), 'advisors' => User::where('role', 'advisor')->where('status', 'active')->count(), 'companies' => Company::where('verified', true)->count(), 'placements' => DB::table('placement_records')->count()], 'advisors' => DB::table('advisor_profiles')->join('users', 'users.id', '=', 'advisor_profiles.user_id')->where('users.status', 'active')->whereNull('users.deleted_at')->select('users.name', 'advisor_profiles.title', 'advisor_profiles.bio')->limit(3)->get(), 'opportunities' => Opportunity::with('company:id,name')->where('status', 'open')->whereDate('deadline', '>=', now('Asia/Dhaka')->toDateString())->latest()->limit(3)->get()->map(fn ($o) => ['title' => $o->title, 'type' => $o->type, 'location' => $o->location, 'company' => $o->company->name]), 'settings' => $this->publicSettings()]];
    }

    public function logo()
    {
        $path = PlatformSettings::get('brand_logo_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

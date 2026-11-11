<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\PlatformReports;
use App\Services\PlatformSettings;
use App\Services\WorkflowActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    private function contrastRatio(string $foreground, string $background): float
    {
        $luminance = function (string $color): float {
            $channels = array_map(
                fn ($channel) => hexdec($channel) / 255,
                str_split(ltrim($color, '#'), 2)
            );
            $linear = array_map(
                fn ($channel) => $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4,
                $channels
            );

            return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
        };
        $colors = [$luminance($foreground), $luminance($background)];

        return (max($colors) + 0.05) / (min($colors) + 0.05);
    }

    private function access()
    {
        $this->authorize('create', User::class);
    }

    public function users(Request $r)
    {
        $this->access();
        $r->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|in:active,pending,suspended', 'role' => 'nullable|in:student,advisor,admin,recruiter', 'page' => 'integer|min:1']);

        return ['data' => User::query()->when($r->q, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$r->q.'%')->orWhere('email', 'like', '%'.$r->q.'%')))->when($r->status, fn ($q) => $q->where('status', $r->status))->when($r->role, fn ($q) => $q->where('role', $r->role))->orderByDesc('id')->paginate(20)->through(fn ($u) => $u->only(['id', 'name', 'email', 'role', 'status', 'company_id', 'must_change_password']))];
    }

    public function update(Request $r, User $user)
    {
        $this->access();
        $d = $r->validate(['status' => 'sometimes|in:active,pending,suspended', 'role' => 'sometimes|in:admin', 'password' => 'sometimes|string|min:10|max:255|regex:/[A-Za-z]/|regex:/[0-9]/']);
        DB::transaction(function () use ($r, $user, $d) {
            $admins = User::where('role', 'admin')->where('status', 'active')->lockForUpdate()->get();
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $roleChanged = isset($d['role']) && $u->role->value !== $d['role'];
            abort_if($roleChanged && $u->role->value !== 'advisor', 422, 'Only advisor accounts can be promoted to administrator.');
            abort_if($u->id === $r->user()->id && isset($d['status']) && $d['status'] !== 'active', 422, 'You cannot disable your own account.');
            abort_if($u->role->value === 'admin' && $u->status === 'active' && isset($d['status']) && $d['status'] !== 'active' && $admins->count() <= 1, 422, 'Keep at least one active administrator.');
            $u->fill($d);
            if (isset($d['password'])) {
                $u->must_change_password = true;
            }
            $u->save();
            if (isset($d['password']) || $roleChanged || ($d['status'] ?? 'active') !== 'active') {
                $u->tokens()->delete();
            }WorkflowActivity::audit($r->user()->id, 'user.updated', 'user', $u->id, collect($d)->except('password')->all() + ['password_reset' => isset($d['password'])]);
            WorkflowActivity::notify(
                [$u->id],
                'Account updated',
                isset($d['password'])
                    ? 'Your administrator reset your password. Contact them for the temporary password.'
                    : ($roleChanged ? 'Your account role is now admin.' : 'Your account status is '.$u->status)
            );
        });

        return ['message' => 'Account updated.'];
    }

    public function destroy(Request $r, User $user)
    {
        $this->access();
        DB::transaction(function () use ($r, $user) {
            $admins = User::where('role', 'admin')->where('status', 'active')->lockForUpdate()->get();
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($u->id === $r->user()->id, 422, 'You cannot remove your own account.');
            abort_if($u->role->value === 'admin' && $u->status === 'active' && $admins->count() <= 1, 422, 'Keep at least one active administrator.');
            $u->tokens()->delete();
            WorkflowActivity::audit($r->user()->id, 'user.removed', 'user', $u->id, ['role' => $u->role->value]);
            $u->delete();
        });

        return ['message' => 'Account removed.'];
    }

    public function settings(Request $r)
    {
        $this->access();
        $settings = PlatformSettings::all();
        $logoPath = $settings['brand_logo_path'] ?? '';
        unset($settings['brand_logo_path']);

        return ['data' => $settings, 'logo_url' => $logoPath && Storage::disk('local')->exists($logoPath) ? url('/api/public/site-logo').'?v='.substr(hash('sha256', $logoPath), 0, 12) : null];
    }

    public function saveSettings(Request $r)
    {
        $this->access();
        $rules = [
            'academic_session' => 'sometimes|nullable|string|max:100',
            'default_capacity' => 'sometimes|required|integer|min:1|max:200',
            'contact_email' => 'sometimes|nullable|email|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:40',
            'contact_address' => 'sometimes|nullable|string|max:1000',
            'privacy_text' => 'sometimes|required|string|max:10000',
            'terms_text' => 'sometimes|required|string|max:10000',
            'site_name' => 'sometimes|required|string|max:120',
            'university_name' => 'sometimes|required|string|max:180',
            'brand_tagline' => 'sometimes|required|string|max:180',
            'theme_primary_color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', function ($attribute, $value, $fail) {
                if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) && $this->contrastRatio($value, '#FFFFFF') < 4.5) {
                    $fail('The primary color must have at least 4.5:1 contrast with white text.');
                }
            }],
            'theme_primary_dark_color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', function ($attribute, $value, $fail) {
                if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) && $this->contrastRatio($value, '#FFFFFF') < 4.5) {
                    $fail('The primary hover color must have at least 4.5:1 contrast with white text.');
                }
            }],
            'theme_navy_color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', function ($attribute, $value, $fail) {
                if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) && $this->contrastRatio($value, '#FFFFFF') < 4.5) {
                    $fail('The navy color must have at least 4.5:1 contrast with white.');
                }
            }],
            'section_order' => 'sometimes|required|string|max:100',
            'announcement_text' => 'sometimes|nullable|string|max:1000',
        ];
        for ($i = 1; $i <= 4; $i++) {
            $rules["faq_{$i}_question"] = 'sometimes|nullable|string|max:300';
            $rules["faq_{$i}_answer"] = 'sometimes|nullable|string|max:2000';
        }
        foreach (array_keys(PlatformSettings::DEFAULTS) as $key) {
            if (isset($rules[$key]) || $key === 'brand_logo_path') {
                continue;
            }
            $rules[$key] = str_ends_with($key, '_enabled')
                ? 'sometimes|in:0,1'
                : 'sometimes|required|string|max:2000';
        }
        $d = $r->validate($rules);
        abort_if($d === [], 422, 'Provide at least one setting to update.');
        if (isset($d['section_order'])) {
            $order = array_map('trim', explode(',', $d['section_order']));
            $validOrder = ['journey', 'advisors', 'opportunities', 'faq', 'contact'];
            abort_if(count($order) !== count($validOrder) || count(array_unique($order)) !== count($validOrder) || array_diff($order, $validOrder), 422, 'Section order must list journey, advisors, opportunities, faq and contact exactly once.');
            $d['section_order'] = implode(',', $order);
        }
        DB::transaction(function () use ($r, $d) {
            foreach ($d as $k => $v) {
                DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $v, 'created_at' => now(), 'updated_at' => now()]);
            }WorkflowActivity::audit($r->user()->id, 'settings.updated', 'settings', 0, $d);
        });

        return $this->settings($r);
    }

    public function saveLogo(Request $r)
    {
        $this->access();
        $r->validate(['image' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        $file = $r->file('image');
        abort_unless(in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp']) && @getimagesize($file->getRealPath()), 422, 'Choose a valid JPG, PNG or WebP image.');
        $path = $file->store('site', 'local');
        $old = PlatformSettings::get('brand_logo_path');
        try {
            DB::transaction(function () use ($r, $path) {
                DB::table('settings')->updateOrInsert(
                    ['key' => 'brand_logo_path'],
                    ['value' => $path, 'created_at' => now(), 'updated_at' => now()]
                );
                WorkflowActivity::audit($r->user()->id, 'settings.logo.updated', 'settings', 0, ['logo_updated' => true]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        return $this->settings($r);
    }

    public function deleteLogo(Request $r)
    {
        $this->access();
        $old = PlatformSettings::get('brand_logo_path');
        DB::transaction(function () use ($r) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'brand_logo_path'],
                ['value' => '', 'created_at' => now(), 'updated_at' => now()]
            );
            WorkflowActivity::audit($r->user()->id, 'settings.logo.removed', 'settings', 0, ['logo_removed' => true]);
        });
        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return $this->settings($r);
    }

    public function audits(Request $r)
    {
        $this->access();
        $r->validate(['q' => 'nullable|string|max:100', 'page' => 'integer|min:1']);

        return ['data' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.actor_id')->select('audit_logs.*', 'users.name as actor_name')->when($r->q, fn ($q) => $q->where('action', 'like', '%'.$r->q.'%'))->orderByDesc('audit_logs.id')->paginate(20)];
    }

    private function report(Request $r): array
    {
        $this->access();
        $d = $r->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', ...($r->filled('from') ? ['after_or_equal:from'] : [])]]);

        return app(PlatformReports::class)->build($d['from'] ?? null, $d['to'] ?? null);
    }

    public function reports(Request $r)
    {
        return ['data' => $this->report($r)];
    }

    public function csv(Request $r)
    {
        $data = $this->report($r);

        return response()->streamDownload(function () use ($data) {
            $f = fopen('php://output', 'w');
            $write = function ($cells) use ($f) {
                fputcsv($f, array_map(fn ($c) => is_string($c) && preg_match('/^[=+@\-\t\r]/', $c) ? "'".$c : $c, $cells), ',', '"', '');
            };
            $write(['Section', 'Label', 'Value']);
            foreach (['applicants', 'placed_students', 'placement_rate', 'applications'] as $k) {
                $write(['Summary', $k, $data[$k]]);
            }foreach (['funnel', 'advisor_workload', 'top_skills'] as $section) {
                foreach ($data[$section] as $row) {
                    $write([$section, $row->status ?? $row->name, $row->total]);
                }
            }fclose($f);
        }, 'ccp-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function company(Request $r)
    {
        $this->access();
        $d = $r->validate(['name' => 'required|string|max:255', 'industry' => 'nullable|string|max:255', 'website' => 'nullable|url:http,https|max:255', 'description' => 'nullable|string|max:5000']);
        $c = Company::create($d + ['verified' => false]);
        WorkflowActivity::audit($r->user()->id, 'company.created', 'company', $c->id, $d);

        return response()->json(['data' => $c->only(['id', 'name', 'verified'])], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowRequest as Request;
use App\Http\Resources\CvResource;
use App\Models\Application;
use App\Models\Cv;
use App\Models\User;
use App\Policies\WorkspacePolicy;
use App\Services\WorkflowActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CvLifecycleController extends Controller
{
    private function access(Request $r, Cv $cv): void
    {
        $u = $r->user();
        $allowed = (new WorkspacePolicy)->student($u, $cv->user_id);
        if ($u->role->value === 'recruiter') {
            $allowed = $u->company_id && Application::forCompany($u->company_id)->where('cv_id', $cv->id)->exists();
        }abort_unless($allowed, 403);
    }

    private function payload(Cv $cv): array
    {
        return (new CvResource($cv))->resolve();
    }

    public function index(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $u = $r->user();
        $q = Cv::with('student')->whereNull('deleted_at');
        if ($u->role->value === 'student') {
            $q->where('user_id', $u->id);
        } elseif ($u->role->value === 'advisor') {
            $q->whereIn('user_id', DB::table('advisor_assignments')->where('advisor_id', $u->id)->where('status', 'active')->pluck('student_id'));
        } else {
            abort(403);
        }
        $rows = $q->orderByDesc('id')->paginate($r->integer('per_page', 20));
        $rows->setCollection($rows->getCollection()->map(fn ($cv) => $this->payload($cv)));

        return ['data' => $rows];
    }

    public function create(Request $r)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        abort_unless($r->user()->role->value === 'student', 403);
        $d = $r->validated();
        $path = null;
        if ($r->hasFile('file')) {
            $file = $r->file('file');
            abort_unless(in_array(strtolower($file->getClientOriginalExtension()), ['pdf', 'docx']), 422, 'Only PDF and DOCX files are allowed.');
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
            $ext = strtolower($file->getClientOriginalExtension());
            abort_unless(($ext === 'pdf' && $mime === 'application/pdf') || ($ext === 'docx' && in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])), 422, 'File content does not match PDF or DOCX.');
            if ($ext === 'docx') {
                $zip = new \ZipArchive;
                abort_unless($zip->open($file->getRealPath()) === true, 422, 'Invalid DOCX archive.');
                $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName('word/document.xml') !== false;
                $zip->close();
                abort_unless($valid, 422, 'Invalid DOCX document.');
            }
            $path = $file->store('cvs', 'local');
        }
        try {
            return DB::transaction(function () use ($r, $d, $path) {
                User::whereKey($r->user()->id)->lockForUpdate()->first();
                $cv = Cv::create(['user_id' => $r->user()->id, 'version' => (Cv::where('user_id', $r->user()->id)->max('version') ?? 0) + 1, 'title' => $d['title'], 'source' => $d['source'], 'content' => $d['source'] === 'builder' ? json_encode($d['content']) : null, 'path' => $path ?? '', 'file_path' => $path, 'original_name' => $r->file('file')?->getClientOriginalName(), 'mime' => $r->file('file')?->getMimeType(), 'size' => $r->file('file')?->getSize(), 'status' => 'draft']);

                return response()->json(['data' => $this->payload($cv)], 201);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
    }

    public function submit(Request $r, Cv $cv)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $cv);
        abort_unless($r->user()->role->value === 'student', 403);

        return DB::transaction(function () use ($cv) {
            $cv = Cv::whereKey($cv->id)->lockForUpdate()->first();
            abort_unless($cv->status === 'draft', 422, 'Only a draft can be submitted. Create a new version to make changes.');
            abort_unless(DB::table('advisor_assignments')->where('student_id', $cv->user_id)->where('status', 'active')->exists(), 422, 'An active advisor is required.');
            $cv->update(['status' => 'submitted', 'submitted_at' => now()]);
            WorkflowActivity::notify([DB::table('advisor_assignments')->where('student_id', $cv->user_id)->where('status', 'active')->value('advisor_id')], 'CV submitted for review', 'Version '.$cv->version);

            return ['data' => $this->payload($cv)];
        });
    }

    public function review(Request $r, Cv $cv)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $cv);
        abort_unless($r->user()->role->value === 'advisor', 403);
        $d = $r->validated();

        return DB::transaction(function () use ($r, $cv, $d) {
            User::whereKey($cv->user_id)->lockForUpdate()->first();
            $this->access($r, $cv);
            $cv = Cv::whereKey($cv->id)->lockForUpdate()->first();
            abort_unless(in_array($cv->status, ['submitted', 'pending']), 422, 'This version is not awaiting review.');
            if ($d['decision'] === 'approved') {
                Cv::where('user_id', $cv->user_id)->where('status', 'approved')->update(['status' => 'superseded']);
            }
            $cv->update(['status' => $d['decision'], 'reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);
            DB::table('cv_reviews')->insert($d + ['cv_id' => $cv->id, 'advisor_id' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            WorkflowActivity::notify([$cv->user_id], 'CV v'.$cv->version.': '.$d['decision'], $d['improvements'], 'general', '/cv');
            WorkflowActivity::audit($r->user()->id, 'cv.review', 'cv', $cv->id, $d);

            return ['data' => $this->payload($cv)];
        });
    }

    public function show(Request $r, Cv $cv)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $cv);

        return ['data' => $this->payload($cv)];
    }

    public function download(Request $r, Cv $cv)
    {
        $this->authorize('workflow', [class_basename($this), $r->route()->getActionMethod()]);
        $this->access($r, $cv);
        $path = $cv->file_path ?: $cv->path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'No uploaded file. Use the builder preview and print to PDF.');

        return Storage::disk('local')->download($path, $cv->original_name ?? 'cv.pdf');
    }
}

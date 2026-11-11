<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();
        if ($u->role->value === 'student') {
            return Note::where('student_id', $u->id)->where('private', false)->latest()->get();
        }
        $sid = $r->query('student_id');
        abort_unless(DB::table('advisor_assignments')->where('student_id', $sid)->where('advisor_id', $u->id)->where('status', 'active')->exists(), 403);

        return Note::where('student_id', $sid)->where(fn ($q) => $q->where('private', false)->orWhere('user_id', $u->id))->latest()->get();
    }

    public function store(Request $r)
    {
        $d = $r->validate(['body' => 'required|string|max:2000', 'student_id' => 'nullable|exists:users,id']);
        $u = $r->user();
        if ($u->role->value === 'student') {
            $sid = $u->id;
            $private = false;
        } else {
            $sid = $d['student_id'] ?? null;
            abort_unless($sid && DB::table('advisor_assignments')->where('student_id', $sid)->where('advisor_id', $u->id)->where('status', 'active')->exists(), 403);
            $private = true;
        }

        return response()->json(Note::create(['user_id' => $u->id, 'student_id' => $sid, 'body' => $d['body'], 'private' => $private]), 201);
    }
}

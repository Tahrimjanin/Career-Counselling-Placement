<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function save(Request $r)
    {
        $this->authorize('updateProfile', $r->user());
        $r->validate(['image' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        $file = $r->file('image');
        abort_unless(in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp']) && @getimagesize($file->getRealPath()), 422, 'Choose a valid JPG, PNG or WebP image.');
        $old = $r->user()->avatar_path;
        $path = $file->store('avatars', 'local');
        try {
            $r->user()->forceFill(['avatar_path' => $path])->save();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }if ($old) {
            Storage::disk('local')->delete($old);
        }

return new UserResource($r->user());
    }

    public function show(Request $r)
    {
        $this->authorize('updateProfile', $r->user());
        $p = $r->user()->avatar_path;
        abort_unless($p && Storage::disk('local')->exists($p), 404);

        return Storage::disk('local')->response($p, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function delete(Request $r)
    {
        $this->authorize('updateProfile', $r->user());
        $old = $r->user()->avatar_path;
        $r->user()->forceFill(['avatar_path' => null])->save();
        if ($old) {
            Storage::disk('local')->delete($old);
        }

return new UserResource($r->user());
    }
}

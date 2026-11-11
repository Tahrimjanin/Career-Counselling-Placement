<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['has_avatar' => (bool) $this->avatar_path, 'avatar_version' => hash('sha256', $this->avatar_path ?? ''), 'id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $this->role->value, 'email_verification_required' => (bool) config('ccp.require_email_verification'), 'email_verified' => $this->hasVerifiedEmail(), 'must_change_password' => $this->must_change_password, 'status' => $this->status, 'department' => $this->department];
    }
}

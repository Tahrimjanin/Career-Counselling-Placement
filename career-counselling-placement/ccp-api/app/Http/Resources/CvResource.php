<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CvResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'user_id' => $this->user_id, 'student_name' => $this->student->name, 'version' => $this->version, 'title' => $this->title, 'source' => $this->source, 'content' => $this->content ? json_decode($this->content, true) : null, 'status' => $this->status, 'submitted_at' => $this->submitted_at, 'reviewed_at' => $this->reviewed_at, 'reviewed_by' => $this->reviewed_by, 'reviews' => DB::table('cv_reviews')->where('cv_id', $this->id)->orderByDesc('id')->get()];
    }
}

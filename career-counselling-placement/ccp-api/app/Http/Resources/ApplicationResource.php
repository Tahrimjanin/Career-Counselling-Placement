<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = $this->resource->only(['id', 'user_id', 'student_id', 'opportunity_id', 'cv_id', 'cover_note', 'status', 'workflow_status', 'eligibility_snapshot', 'submitted_at', 'admin_review', 'admin_decision', 'admin_reason', 'admin_reviewed_by', 'admin_reviewed_at', 'forwarded_at', 'forwarded_by', 'withdrawn_at', 'created_at', 'updated_at']);
        foreach (['user', 'cv', 'interview'] as $relation) {
            if ($this->resource->relationLoaded($relation)) {
                $data[$relation] = $this->resource->getRelation($relation);
            }
        }
        if ($this->resource->relationLoaded('opportunity')) {
            $data['opportunity'] = (new OpportunityResource($this->opportunity))->resolve();
        }

        return $data;
    }
}

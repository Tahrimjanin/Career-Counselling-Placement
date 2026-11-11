<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = $this->resource->only(['id', 'company_id', 'posted_by', 'title', 'type', 'location', 'work_mode', 'is_paid', 'stipend_min', 'stipend_max', 'stipend_label', 'description', 'responsibilities', 'requirements', 'skills', 'min_cgpa', 'deadline', 'status', 'approved_by', 'rejection_reason', 'created_at', 'updated_at']);
        if ($this->resource->relationLoaded('company')) {
            $data['company'] = $this->company?->only(['id', 'name']);
        }
        foreach (['matched_skills', 'missing_skills', 'match_percent', 'saved'] as $key) {
            if (array_key_exists($key, $this->resource->getAttributes())) {
                $data[$key] = $this->$key;
            }
        }

        return $data;
    }
}

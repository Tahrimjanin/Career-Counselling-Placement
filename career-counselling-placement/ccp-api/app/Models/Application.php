<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $guarded = [];

    protected $casts = ['forwarded_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function cv()
    {
        return $this->belongsTo(Cv::class);
    }

    public function interview()
    {
        return $this->hasOne(Interview::class);
    }

    public function scopeForCompany($q, $cid)
    {
        return $q->whereNotNull('forwarded_at')->whereHas('opportunity', fn ($w) => $w->where('company_id', $cid));
    }
}

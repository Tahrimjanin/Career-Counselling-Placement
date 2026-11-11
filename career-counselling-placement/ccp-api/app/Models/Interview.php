<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $guarded = [];

    protected $casts = ['scheduled_at' => 'datetime'];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}

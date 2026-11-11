<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $guarded = [];

    protected $casts = ['verified' => 'boolean'];

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }
}

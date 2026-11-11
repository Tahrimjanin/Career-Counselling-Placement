<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cv extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role', 'status', 'phone', 'department', 'skills', 'advisor_id', 'company_id', 'must_change_password', 'last_login_at', 'email_verified_at'];

    protected $attributes = ['status' => 'active', 'role' => 'student'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'skills' => 'array', 'role' => Role::class, 'email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'must_change_password' => 'boolean'];
    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }
}

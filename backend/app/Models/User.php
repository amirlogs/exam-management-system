<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['first_name', 'last_name', 'email', 'password', 'is_first_login'];

    protected $hidden = ['password'];

    protected $casts = ['is_first_login' => 'boolean'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** All permission names this user holds across every role assignment. */
    public function permissionNames(): array
    {
        return $this->userRoles()
            ->with('role.permissions')
            ->get()
            ->pluck('role.permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }
}

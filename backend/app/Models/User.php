<?php

namespace App\Models;

use App\Student;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['email', 'password', 'is_first_login'];

    protected $hidden = ['password'];

    protected $casts = ['is_first_login' => 'boolean'];

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

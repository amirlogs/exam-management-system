<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    const UPDATED_AT = null;

    public function users()
    {
        return $this->belongsToMany(User::class, "user_role_permission");
    }
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            "role_permissions",
        )->withTimestamps();
    }
}

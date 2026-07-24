<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    const UPDATED_AT = null;

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role_permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function userRoles(): BelongsToMany
    {
        return $this->belongsToMany(UserRole::class, 'user_role_permissions')->withPivot(['is_granted', 'set_by', 'set_at']);
    }
}

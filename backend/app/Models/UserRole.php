<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    // const UPDATED_AT = null;
    public $timestamps = false;

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_role_permissions')->withPivot(['is_granted', 'set_by', 'set_at']);
    }

    public function deniedPermissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'user_role_permissions'
        )->wherePivot('is_granted', false);
    }
}

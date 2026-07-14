<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    public function permisssion()
    {
        return $this->hasMany(UserRolePermission::class);
    }
    public function user()
    {
        $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function assignedBy()
    {
        return $this->belongsTo(User::class, "assigned_by");
    }
}

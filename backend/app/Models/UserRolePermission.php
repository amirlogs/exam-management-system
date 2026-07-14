<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRolePermission extends Model
{
    public $timestamps = false;
    public function user()
    {
        $this->belongsTo(User::class);
    }

    public function permission()
    {
        $this->belongsTo(Permission::class);
    }

    public function userRole()
    {
        return $this->belongsTo(UserRole::class);
    }
    public function setBy()
    {
        return $this->belongsTo(User::class, "set_by");
    }
}

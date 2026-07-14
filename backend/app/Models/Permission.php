<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class , 'user_role_permission');
    }
    public function role(){
        return $this->belongsToMany(Role::class , 'role_permission');
    }
}

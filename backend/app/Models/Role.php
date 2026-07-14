<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\FuncCall;
use Psy\CodeCleaner\FunctionContextPass;

class Role extends Model
{
    const UPDATED_AT = null;

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot("department_id", "assigned_by", "assigned_at")
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            "role_permissions",
        );
    }
}

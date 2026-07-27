<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    protected $primaryKey = "user_id";
    public $incrementing = false;
    protected $keyType = "int";

    protected $fillable = ["user_id", "staff_id", "home_department_id"];
    function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
    function homedepartment()
    {
        return $this->belongsTo(Department::class, "home_department_id");
    }
}

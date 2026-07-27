<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = "user_id";
    public $incrementing = false;
    protected $keyType = "int";
    protected $fillable = ["user_id", "section_id", "universty_id"];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}

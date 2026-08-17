<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use SoftDeletes;
    protected $fillable = ['semester_id', 'program_id', 'year_level', 'name'];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}

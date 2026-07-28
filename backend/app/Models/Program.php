<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['department_id', 'name', 'duration_years'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function curriculums()
    {
        return $this->hasMany(Curriculum::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}

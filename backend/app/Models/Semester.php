<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use SoftDeletes;

    protected $fillable = ['academic_year', 'name', 'term_number', 'start_date', 'end_date', 'status'];
    
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function activate(): void
    {
        DB::transaction(function () {
            static::where('status', 'active')->update(['status' => 'completed']);
            $this->update(['status' => 'active']);
        });

    }

    public function close()
    {
        $this->update(['status' => 'completed']);
    }

    public function state(): string
    {
        return $this->status;
    }
}

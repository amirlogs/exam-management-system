<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class University extends Model
{
    use SoftDeletes;
    protected $table = 'universities';

    protected $fillable = ['name', 'code', 'address'];


    public function colleges()
    {
        return $this->hasMany(College::class);
    }
}

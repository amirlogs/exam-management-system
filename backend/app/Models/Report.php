<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['type', 'params', 'generated_by', 'file_path', 'status'];

    protected $casts = ['params' => 'array'];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

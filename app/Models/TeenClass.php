<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeenClass extends Model
{
     protected $fillable = [
        'name',
        'age_from',
        'age_to',
        'class_teacher',
        'total_teens',
        'description',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

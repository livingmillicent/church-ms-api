<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildMinistry extends Model
{
    protected $table = 'children_ministries';

    protected $fillable = [
        'name',
        'age_from',
        'age_to',
        'class_teacher',
        'total_children',
        'description',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}

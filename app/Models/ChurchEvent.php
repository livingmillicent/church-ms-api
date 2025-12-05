<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChurchEvent extends Model
{
    use HasFactory;

    protected $table = 'church_events';

    protected $fillable = [
        'title',
        'description',
        'event_type',
        'start_time',
        'end_time',
        'location',
        'participants',
        'speakers',
        'budget',
        'status',
        'max_attendees',
        'requires_registration'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'participants' => 'array',
        'speakers' => 'array',
        'budget' => 'decimal:2',
        'requires_registration' => 'boolean'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getAttendeeCountAttribute()
    {
        return $this->attendances()->count();
    }
}

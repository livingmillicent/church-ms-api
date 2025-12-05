<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';
    protected $fillable = [
        'member_id',
        'event_id',
    ];

    public function member()
    {
        return $this->belongsTo(ChurchMember::class, 'member_id');
    }

    public function event()
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }
}

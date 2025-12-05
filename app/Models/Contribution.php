<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contribution extends Model
{
    use HasFactory;

    protected $table = 'contributions';

    protected $fillable = [
        'user_id',
        'member_type',
        'baptism_date',
        'marital_status',
        'emergency_contact',
        'family_members',
        'spiritual_gifts',
        'ministries',
        'home_cell_group'
    ];

    protected $casts = [
        'family_members' => 'array',
        'spiritual_gifts' => 'array',
        'ministries' => 'array',
        'baptism_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function member()
    {
        return $this->belongsTo(ChurchMember::class, 'member_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getTotalContributionsAttribute()
    {
        return $this->contributions()->sum('amount');
    }
}

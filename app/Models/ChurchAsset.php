<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChurchAsset extends Model
{
     use HasFactory;

    protected $table = 'church_assets'; 

    protected $fillable = [
        'name',
        'asset_type',
        'description',
        'value',
        'purchase_date',
        'location',
        'condition',
        'is_available',
        'maintenance_schedule',
        'assigned_to'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'value' => 'decimal:2',
        'is_available' => 'boolean',
        'maintenance_schedule' => 'array'
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

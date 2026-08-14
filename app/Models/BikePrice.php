<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BikePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'bike_id',
        'effective_from',
        'price',
        'billing_type',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function bike()
    {
        return $this->belongsTo(Bike::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_number',
        'user_id',
        'bike_id',
        'quantity',
        'station_id',
        'status',
        'payment_status',
        'payment_method',
        'paid_at',
        'starts_at',
        'ends_at',
        'total_price',
        'notes',
        'picked_up_at',
        'returned_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'paid_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bike()
    {
        return $this->belongsTo(Bike::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(ReservationDay::class)->orderBy('reservation_date');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bike extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id',
        'code',
        'name',
        'size',
        'stock_quantity',
        'type',
        'status',
        'battery_level',
        'price_per_hour',
        'description',
        'image_url',
        'last_service_at',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'battery_level' => 'integer',
        'price_per_hour' => 'decimal:2',
        'last_service_at' => 'date',
    ];

    protected $hidden = [
        'price_per_hour',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function reservationSettings()
    {
        return $this->belongsToMany(ReservationSetting::class, 'bike_reservation_setting')->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(BikePrice::class);
    }
}

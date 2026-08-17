<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationDay extends Model
{
    protected $fillable = [
        'reservation_id',
        'reservation_date',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}

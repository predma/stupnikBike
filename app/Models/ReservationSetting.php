<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReservationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mode',
        'effective_from',
        'slots',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'slots' => 'array',
        'is_active' => 'boolean',
    ];

    public function bikes(): BelongsToMany
    {
        return $this->belongsToMany(Bike::class, 'bike_reservation_setting')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function normalizedSlots(): array
    {
        return array_values(array_filter(array_map(static function (array|string $slot): ?array {
            if (is_string($slot)) {
                $slot = trim($slot);
                if (! str_contains($slot, '-')) {
                    return null;
                }

                [$start, $end] = array_map('trim', explode('-', $slot, 2));
                return $start && $end ? ['start' => $start, 'end' => $end] : null;
            }

            $start = trim((string) ($slot['start'] ?? ''));
            $end = trim((string) ($slot['end'] ?? ''));

            return $start && $end ? ['start' => $start, 'end' => $end] : null;
        }, $this->slots ?? [])));
    }

    public function isHourly(): bool
    {
        return $this->mode === 'hourly';
    }

    public function isDaily(): bool
    {
        return $this->mode === 'daily';
    }

    public static function activeForBike(Bike $bike, Carbon|string|null $date = null): ?self
    {
        $date = $date ? Carbon::parse($date) : now();

        return static::query()
            ->active()
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->whereHas('bikes', fn ($query) => $query->whereKey($bike->getKey()))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}

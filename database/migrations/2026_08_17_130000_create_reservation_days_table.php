<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->date('reservation_date');
            $table->timestamps();

            $table->unique(['reservation_id', 'reservation_date']);
            $table->index('reservation_date');
        });

        DB::table('reservations')
            ->select(['id', 'starts_at', 'ends_at'])
            ->orderBy('id')
            ->get()
            ->each(function (object $reservation): void {
                $start = CarbonImmutable::parse($reservation->starts_at)->startOfDay();
                $end = CarbonImmutable::parse($reservation->ends_at)->startOfDay();
                $days = max(1, (int) $start->diffInDays($end) + 1);

                foreach (range(0, $days - 1) as $offset) {
                    DB::table('reservation_days')->insertOrIgnore([
                        'reservation_id' => $reservation->id,
                        'reservation_date' => $start->addDays($offset)->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_days');
    }
};

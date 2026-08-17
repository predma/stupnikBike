<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mode')->default('daily');
            $table->date('effective_from');
            $table->unsignedSmallInteger('max_days_per_reservation')->default(1);
            $table->json('slots')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bike_reservation_setting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_setting_id')->constrained('reservation_settings')->cascadeOnDelete();
            $table->foreignId('bike_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['reservation_setting_id', 'bike_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_reservation_setting');
        Schema::dropIfExists('reservation_settings');
    }
};

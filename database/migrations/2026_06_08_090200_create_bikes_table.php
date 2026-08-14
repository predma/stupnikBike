<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('standard');
            $table->string('status')->default('available');
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->decimal('price_per_hour', 8, 2)->default(2.50);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->date('last_service_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};

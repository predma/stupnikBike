<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bike_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->decimal('price', 10, 2);
            $table->string('billing_type')->default('daily');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['bike_id', 'effective_from', 'billing_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bike_prices');
    }
};

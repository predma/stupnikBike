<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->unsignedSmallInteger('gear_count')->nullable()->after('battery_level');
            $table->text('equipment')->nullable()->after('description');
            $table->text('technical_details')->nullable()->after('equipment');
        });
    }

    public function down(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->dropColumn(['gear_count', 'equipment', 'technical_details']);
        });
    }
};

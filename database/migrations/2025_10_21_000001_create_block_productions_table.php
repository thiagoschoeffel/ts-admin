<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_pointing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_type_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->unsignedInteger('sheet_number');
            $table->decimal('weight', 12, 2);
            $table->unsignedInteger('length_mm')->default(4060);
            $table->unsignedInteger('width_mm')->default(1020);
            $table->unsignedInteger('height_mm');
            $table->timestamps();
        });

        Schema::create('block_production_operator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_production_id')->constrained('block_productions')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('block_production_silo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_production_id')->constrained('block_productions')->cascadeOnDelete();
            $table->foreignId('silo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_production_silo');
        Schema::dropIfExists('block_production_operator');
        Schema::dropIfExists('block_productions');
    }
};


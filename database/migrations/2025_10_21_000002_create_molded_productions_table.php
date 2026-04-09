<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('molded_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_pointing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mold_type_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->unsignedInteger('sheet_number');
            $table->unsignedInteger('quantity');
            $table->decimal('package_weight', 12, 2);
            $table->unsignedInteger('package_quantity');
            $table->decimal('weight_considered_unit', 12, 3);
            $table->decimal('total_weight_considered', 12, 2);
            $table->timestamps();
        });

        Schema::create('molded_production_operator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('molded_production_id')->constrained('molded_productions')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('molded_production_silo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('molded_production_id')->constrained('molded_productions')->cascadeOnDelete();
            $table->foreignId('silo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('molded_production_silo');
        Schema::dropIfExists('molded_production_operator');
        Schema::dropIfExists('molded_productions');
    }
};


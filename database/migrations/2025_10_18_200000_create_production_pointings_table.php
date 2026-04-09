<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_pointings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sheet_number')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedBigInteger('raw_material_id')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('raw_material_id')->references('id')->on('raw_materials')->nullOnDelete();
        });

        Schema::create('production_pointing_operator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_pointing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['production_pointing_id', 'operator_id'], 'ppo_unique');
        });

        Schema::create('production_pointing_silo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_pointing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('silo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['production_pointing_id', 'silo_id'], 'pps_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_pointing_silo');
        Schema::dropIfExists('production_pointing_operator');
        Schema::dropIfExists('production_pointings');
    }
};

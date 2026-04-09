<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_pointing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->decimal('reserved_kg', 12, 3)->default(0);
            $table->decimal('consumed_kg', 12, 3)->default(0);
            $table->enum('status', ['active', 'closed', 'canceled'])->default('active');
            $table->timestamps();
            $table->unique('production_pointing_id');
            $table->index(['raw_material_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};


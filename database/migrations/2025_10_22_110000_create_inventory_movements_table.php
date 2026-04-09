<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at');
            $table->enum('item_type', ['raw_material', 'block', 'molded']);
            $table->unsignedBigInteger('item_id');
            $table->enum('location_type', ['silo', 'almoxarifado', 'none'])->default('none');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->enum('direction', ['in', 'out', 'reserve', 'release', 'adjust']);
            $table->decimal('quantity', 12, 3); // quantity in given unit
            $table->enum('unit', ['kg', 'unit'])->default('kg');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index(['location_type', 'location_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};


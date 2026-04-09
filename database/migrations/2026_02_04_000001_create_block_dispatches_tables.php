<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_dispatches', function (Blueprint $table) {
            $table->id();
            $table->dateTime('dispatched_at');
            $table->string('manufacturing_order_number', 60);
            $table->foreignId('production_pointing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('block_dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_dispatch_id')->constrained('block_dispatches')->cascadeOnDelete();
            $table->foreignId('block_production_id')->constrained('block_productions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('block_production_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_dispatch_items');
        Schema::dropIfExists('block_dispatches');
    }
};


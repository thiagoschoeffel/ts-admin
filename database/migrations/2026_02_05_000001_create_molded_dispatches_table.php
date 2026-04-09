<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('molded_dispatches', function (Blueprint $table) {
            $table->id();
            $table->dateTime('dispatched_at');
            $table->string('manufacturing_order_number', 60);
            $table->foreignId('mold_type_id')->constrained('mold_types');
            $table->unsignedInteger('quantity'); // unidades
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('molded_dispatches');
    }
};


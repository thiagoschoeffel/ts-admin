<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('component_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};

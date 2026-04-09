<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('opportunity_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('opportunity_id')->constrained('opportunities')->restrictOnDelete();
      $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
      $table->decimal('quantity', 10, 2);
      $table->decimal('unit_price', 12, 2);
      $table->decimal('subtotal', 12, 2);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('opportunity_items');
  }
};

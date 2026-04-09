<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('opportunities', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lead_id')->nullable()->constrained('leads')->restrictOnDelete();
      $table->foreignId('client_id')->nullable()->constrained('clients')->restrictOnDelete();
      $table->string('title', 200);
      $table->text('description')->nullable();
      $table->enum('stage', ['new', 'contact', 'proposal', 'negotiation', 'won', 'lost'])->default('new');
      $table->unsignedTinyInteger('probability')->default(0);
      $table->decimal('expected_value', 12, 2)->default(0);
      $table->date('expected_close_date')->nullable();
      $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
      $table->enum('status', ['active', 'inactive'])->default('active');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('opportunities');
  }
};

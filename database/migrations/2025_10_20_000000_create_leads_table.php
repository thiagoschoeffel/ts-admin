<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('leads', function (Blueprint $table) {
      $table->id();
      $table->string('name', 180);
      $table->string('email', 180)->nullable()->unique();
      $table->string('phone', 30)->nullable()->unique();
      $table->string('company', 180)->nullable();
      $table->enum('source', ['site', 'indicacao', 'evento', 'manual'])->default('manual');
      $table->enum('status', ['new', 'in_contact', 'qualified', 'discarded'])->default('new');
      $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('leads');
  }
};

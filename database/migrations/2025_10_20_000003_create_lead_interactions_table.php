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
        Schema::create('lead_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnDelete();
            $table->enum('type', ['phone_call', 'email', 'meeting', 'message', 'visit', 'other'])->default('other');
            $table->datetime('interacted_at');
            $table->text('description');
            $table->json('metadata')->nullable(); // Para armazenar dados adicionais como duração, resultado, etc.
            $table->timestamps();

            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_interactions');
    }
};


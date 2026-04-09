<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->restrictOnDelete();
            $table->foreignId('reason_id')->constrained('reasons')->restrictOnDelete();
            $table->dateTime('started_at')->index();
            $table->dateTime('ended_at')->index();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['machine_id', 'reason_id']);
            $table->index(['machine_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_downtimes');
    }
};


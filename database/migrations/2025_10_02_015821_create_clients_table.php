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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('person_type', ['individual', 'company']);
            $table->string('document', 20)->unique();
            $table->text('observations')->nullable();

            $table->string('postal_code', 9);
            $table->string('address');
            $table->string('address_number', 20);
            $table->string('address_complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);

            $table->string('contact_name')->nullable();
            $table->string('contact_phone_primary', 20)->nullable();
            $table->string('contact_phone_secondary', 20)->nullable();
            $table->string('contact_email')->nullable();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

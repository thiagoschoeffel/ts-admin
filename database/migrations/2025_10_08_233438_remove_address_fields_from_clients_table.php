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
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'postal_code',
                'address',
                'address_number',
                'address_complement',
                'neighborhood',
                'city',
                'state',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('postal_code', 9);
            $table->string('address');
            $table->string('address_number', 20);
            $table->string('address_complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
        });
    }
};

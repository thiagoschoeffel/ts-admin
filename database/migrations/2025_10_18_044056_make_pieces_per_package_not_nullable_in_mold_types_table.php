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
        Schema::table('mold_types', function (Blueprint $table) {
            $table->decimal('pieces_per_package', 8, 2)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mold_types', function (Blueprint $table) {
            $table->decimal('pieces_per_package', 8, 2)->nullable()->change();
        });
    }
};

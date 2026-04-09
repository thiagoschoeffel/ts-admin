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
            $table->integer('pieces_per_package')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mold_types', function (Blueprint $table) {
            $table->dropColumn('pieces_per_package');
        });
    }
};

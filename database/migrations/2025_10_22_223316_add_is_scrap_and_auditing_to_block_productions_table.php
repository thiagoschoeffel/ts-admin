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
        Schema::table('block_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('block_productions', 'is_scrap')) {
                $table->boolean('is_scrap')->default(false)->after('dimension_customization_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('block_productions', function (Blueprint $table) {
            if (Schema::hasColumn('block_productions', 'is_scrap')) {
                $table->dropColumn('is_scrap');
            }
        });
    }
};

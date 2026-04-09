<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('block_type_id')->nullable()->after('item_id')->constrained('block_types');
            $table->unsignedInteger('length_mm')->nullable()->after('block_type_id');
            $table->unsignedInteger('width_mm')->nullable()->after('length_mm');
            $table->unsignedInteger('height_mm')->nullable()->after('width_mm');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['block_type_id']);
            $table->dropColumn(['block_type_id', 'length_mm', 'width_mm', 'height_mm']);
        });
    }
};

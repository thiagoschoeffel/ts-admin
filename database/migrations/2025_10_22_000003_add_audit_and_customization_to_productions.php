<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('block_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('block_productions', 'dimension_customization_enabled')) {
                $table->boolean('dimension_customization_enabled')->default(false)->after('height_mm');
            }
            if (!Schema::hasColumn('block_productions', 'created_by_id')) {
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('block_productions', 'updated_by_id')) {
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('molded_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('molded_productions', 'loss_factor_enabled')) {
                $table->boolean('loss_factor_enabled')->default(false)->after('package_quantity');
            }
            if (!Schema::hasColumn('molded_productions', 'loss_factor')) {
                $table->decimal('loss_factor', 5, 4)->nullable()->after('loss_factor_enabled');
            }
            if (!Schema::hasColumn('molded_productions', 'created_by_id')) {
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('molded_productions', 'updated_by_id')) {
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('block_productions', function (Blueprint $table) {
            if (Schema::hasColumn('block_productions', 'dimension_customization_enabled')) {
                $table->dropColumn('dimension_customization_enabled');
            }
            if (Schema::hasColumn('block_productions', 'created_by_id')) {
                $table->dropConstrainedForeignId('created_by_id');
            }
            if (Schema::hasColumn('block_productions', 'updated_by_id')) {
                $table->dropConstrainedForeignId('updated_by_id');
            }
        });

        Schema::table('molded_productions', function (Blueprint $table) {
            if (Schema::hasColumn('molded_productions', 'loss_factor_enabled')) {
                $table->dropColumn('loss_factor_enabled');
            }
            if (Schema::hasColumn('molded_productions', 'loss_factor')) {
                $table->dropColumn('loss_factor');
            }
            if (Schema::hasColumn('molded_productions', 'created_by_id')) {
                $table->dropConstrainedForeignId('created_by_id');
            }
            if (Schema::hasColumn('molded_productions', 'updated_by_id')) {
                $table->dropConstrainedForeignId('updated_by_id');
            }
        });
    }
};

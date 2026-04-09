<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('molded_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('molded_productions', 'scrap_quantity')) {
                $table->unsignedInteger('scrap_quantity')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('molded_productions', 'scrap_reason_id')) {
                $table->foreignId('scrap_reason_id')->nullable()->constrained('reasons')->nullOnDelete()->after('scrap_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('molded_productions', function (Blueprint $table) {
            if (Schema::hasColumn('molded_productions', 'scrap_reason_id')) {
                $table->dropConstrainedForeignId('scrap_reason_id');
            }
            if (Schema::hasColumn('molded_productions', 'scrap_quantity')) {
                $table->dropColumn('scrap_quantity');
            }
        });
    }
};

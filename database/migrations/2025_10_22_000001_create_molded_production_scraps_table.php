<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('molded_production_scraps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('molded_production_id');
            $table->unsignedBigInteger('reason_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->foreign('molded_production_id')->references('id')->on('molded_productions')->onDelete('cascade');
            $table->foreign('reason_id')->references('id')->on('reasons');
        });
    }
    public function down()
    {
        Schema::dropIfExists('molded_production_scraps');
    }
};

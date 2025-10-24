<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('library_editions', function (Blueprint $table) {
            $table->uuid('edition_id');
            $table->uuid('library_id');
            $table->unsignedInteger('stock_total');
            $table->timestamps();

            $table->unique(['edition_id', 'library_id']);
            $table->foreign('edition_id')->references('id')->on('editions')->onDelete('cascade');
            $table->foreign('library_id')->references('id')->on('libraries')->onDelete('cascade');
        });

        DB::statement('ALTER TABLE library_editions ADD CONSTRAINT chk_stock_total_nonneg CHECK (stock_total >= 0)');
    }

    public function down()
    {
        Schema::dropIfExists('library_editions');
    }
};

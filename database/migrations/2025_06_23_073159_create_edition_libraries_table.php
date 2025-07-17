<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edition_libraries', function (Blueprint $table) {
            $table->uuid('edition_id');
            $table->uuid('library_id');
            $table->integer('stock_total');
            $table->integer('stock_available');
            $table->timestamps();

            $table->primary(['edition_id', 'library_id']);
            $table->foreign('edition_id')->references('id')->on('editions')->onDelete('cascade');
            $table->foreign('library_id')->references('id')->on('libraries')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_libraries');
    }
};

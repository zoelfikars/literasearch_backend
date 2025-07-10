<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('isbn_10')->unique()->nullable();
            $table->string('isbn_13')->unique()->nullable();
            $table->integer('edition_number')->nullable();
            $table->date('publication_date')->nullable();
            $table->text('cover')->nullable();
            $table->text('file_path')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('pages')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->uuid('book_id');
            $table->uuid('publisher_id')->nullable();
            $table->uuid('langeuage_id')->nullable();
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('publisher_id')->references('id')->on('publishers')->nullOnDelete();
            $table->foreign('langeuage_id')->references('id')->on('languages')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};

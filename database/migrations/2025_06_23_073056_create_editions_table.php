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
            $table->integer('publication_year')->nullable();
            $table->text('cover')->nullable();
            $table->integer('pages');
            $table->text('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->uuid('book_title_id');
            $table->uuid('publisher_id')->nullable();
            $table->uuid('language_id')->nullable();
            $table->timestamps();

            $table->foreign('book_title_id')->references('id')->on('book_titles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('publisher_id')->references('id')->on('publishers')->nullOnDelete();
            $table->foreign('language_id')->references('id')->on('languages')->nullOnDelete();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};

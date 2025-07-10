<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edition_authors', function (Blueprint $table) {
            $table->uuid('edition_id');
            $table->uuid('author_id');
            $table->string('role')->nullable();
            $table->string('subtitle')->nullable();
            $table->timestamps();

            $table->primary(['edition_id', 'author_id']);
            $table->foreign('edition_id')->references('id')->on('editions')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_authors');
    }
};

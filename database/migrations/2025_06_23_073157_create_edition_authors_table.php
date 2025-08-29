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
            $table->uuid('role_id');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('author_roles')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('authors')->cascadeOnDelete();

            $table->unique(['edition_id', 'author_id', 'role_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_authors');
    }
};

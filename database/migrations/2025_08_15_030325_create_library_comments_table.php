<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('library_id');
            $table->uuid('user_id');
            $table->string('text');
            $table->timestamps();

            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('library_comments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edition_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('edition_id');
            $table->uuid('user_id');
            $table->string('text');
            $table->timestamps();

            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_comments');
    }
};

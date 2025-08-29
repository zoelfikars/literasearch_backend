<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edition_ratings', function (Blueprint $table) {
            $table->uuid('edition_id');
            $table->uuid('user_id');
            $table->tinyInteger('rating');
            $table->text('text')->nullable();
            $table->timestamps();

            $table->primary(['edition_id', 'user_id']);
            $table->foreign('edition_id')->references('id')->on('editions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_user_ratings');
    }
};

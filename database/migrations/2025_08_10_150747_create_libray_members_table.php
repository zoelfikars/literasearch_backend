<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('libray_members', function (Blueprint $table) {
            $table->uuid('library_id');
            $table->uuid('user_id');
            $table->primary(['library_id', 'user_id']);
            $table->boolean('is_active');
            $table->boolean('blacklisted')->default(false);
            $table->timestamps();

            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('libray_members');
    }
};

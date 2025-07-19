<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_guardians', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->text('full_name')->nullable();
            $table->text('nik')->nullable();
            $table->text('birth_place')->nullable();
            $table->text('birth_date')->nullable();
            $table->enum('relationship', ['Ayah', 'Ibu', 'Wali', 'Kakak'])->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->text('address')->nullable();
            $table->string('phone_number', 25)->nullable();
            $table->string('identity_image_path')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_guardians');
    }
};

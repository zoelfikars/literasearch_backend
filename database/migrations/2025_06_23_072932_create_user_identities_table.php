<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_identities', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->text('full_name');
            $table->text('nik');
            $table->string('nik_hash', 64)->index();
            $table->text('birth_place');
            $table->text('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->text('address');
            $table->text('phone_number');
            $table->string('phone_number_hash', 64)->index();
            $table->text('identity_image_path');
            $table->enum('relationship', ['Ayah', 'Ibu', 'Wali', 'Kakak'])->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_identities');
    }
};

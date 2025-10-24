<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('libraries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('phone_number', 15)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('image_path')->nullable();
            $table->boolean('is_recruiting')->default(false);
            $table->boolean('is_active')->default(false);

            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['name', 'address', 'latitude', 'longitude']);
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('libraries');
    }
};

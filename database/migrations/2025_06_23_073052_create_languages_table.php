<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('english_name', 255);
            $table->string('native_name', 255)->nullable();
            $table->char('iso_639_1', 2)->unique()->nullable();
            $table->char('iso_639_3', 3)->unique();
            $table->enum('direction', ['ltr', 'rtl']);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};

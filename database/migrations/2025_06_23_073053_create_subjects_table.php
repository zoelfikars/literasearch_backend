<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug');

            $table->timestamps();
            $table->unique(['slug']);

        });
    }
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};

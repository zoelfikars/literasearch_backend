<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug_name');
            $table->string('city');
            $table->string('slug_city');
            $table->text('address')->nullable();

            $table->timestamps();
            $table->unique(['slug_name','slug_city']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('publishers');
    }
};

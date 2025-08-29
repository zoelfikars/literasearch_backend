<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edition_reads', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('edition_id');
            $table->integer('pages')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_reads');
    }
};

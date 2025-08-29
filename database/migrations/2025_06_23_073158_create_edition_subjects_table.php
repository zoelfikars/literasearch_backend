<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edition_subjects', function (Blueprint $table) {
            $table->uuid('subject_id');
            $table->uuid('edition_id');
            $table->timestamps();

            $table->primary(['subject_id', 'edition_id']);
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_subjects');
    }
};

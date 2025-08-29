<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('librarian_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('library_id');
            $table->uuid('user_id');
            $table->uuid('status_id');
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->unique(['library_id', 'user_id', 'status_id']);
            $table->timestamps();
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('status_id')->references('id')->on('statuses')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('librarian_applications');
    }
};

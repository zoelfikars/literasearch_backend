<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('librarian_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('library_id');
            $table->uuid('user_id');
            $table->uuid('status_id');
            $table->uuid('inspector_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('inspected_at')->nullable();

            $table->timestamps();
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('status_id')->references('id')->on('statuses')->cascadeOnDelete();
            $table->foreign('inspector_id')->references('id')->on('users')->nullOnDelete();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('librarian_applications');
    }
};

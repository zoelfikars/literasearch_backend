<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('edition_id');
            $table->uuid('library_id');
            $table->timestamp('loaned_at');
            $table->timestamp('due_date');
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('inspector_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('status_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->foreign('inspector_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('status_id')->references('id')->on('statuses')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

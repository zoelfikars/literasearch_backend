<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('library_id')->index();
            $table->uuid('status_id')->index();
            $table->uuid('reviewed_by')->nullable()->index();
            $table->text('rejected_reason')->nullable();
            $table->date('expiration_date');
            $table->string('document_path')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('library_id')->references('id')->on('libraries')->cascadeOnDelete();
            $table->foreign('status_id')->references('id')->on('statuses')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('library_applications');
    }
};

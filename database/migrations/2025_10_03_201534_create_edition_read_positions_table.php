<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('edition_read_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('edition_id');
            $table->enum('locator_type', ['page', 'cfi'])->index();
            $table->unsignedInteger('page')->nullable();
            $table->text('cfi')->nullable(); // pakai text kalau CFI panjang
            $table->decimal('progress_percent', 5, 2)->nullable(); // 0..100
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'edition_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('edition_read_positions');
    }
};

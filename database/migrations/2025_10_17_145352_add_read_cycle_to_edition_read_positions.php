<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('edition_read_positions', function (Blueprint $table) {
            $table->unsignedInteger('read_cycle')->default(0)->after('edition_id');
        });
    }
    public function down(): void
    {
        Schema::table('edition_read_positions', function (Blueprint $table) {
            $table->dropColumn('read_cycle');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->decimal('advance', 10, 2)->default(0)->after('telegram')->comment('Аванс');
            $table->decimal('balance', 10, 2)->default(0)->after('advance')->comment('Залишок (вартість - аванс)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->dropColumn(['advance', 'balance']);
        });
    }
};

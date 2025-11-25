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
            $table->string('telegram')->nullable()->after('phone')->comment('Телеграм nickname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->dropColumn('telegram');
        });
    }
};

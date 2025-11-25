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
            $table->text('info')->nullable()->after('has_transfer_back')->comment('Інформація');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->dropColumn('info');
        });
    }
};

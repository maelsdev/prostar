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
            $table->boolean('has_transfer_there')->default(false)->after('balance')->comment('Трансфер туди');
            $table->boolean('has_transfer_back')->default(false)->after('has_transfer_there')->comment('Трансфер назад');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->dropColumn(['has_transfer_there', 'has_transfer_back']);
        });
    }
};

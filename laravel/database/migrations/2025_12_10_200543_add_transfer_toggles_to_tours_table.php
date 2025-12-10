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
        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('has_transfer_to_tour')->default(false)->after('transfer_price_from_tour');
            $table->boolean('has_transfer_from_tour')->default(false)->after('has_transfer_to_tour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['has_transfer_to_tour', 'has_transfer_from_tour']);
        });
    }
};

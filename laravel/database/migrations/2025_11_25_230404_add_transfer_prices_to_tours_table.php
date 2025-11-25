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
            $table->decimal('transfer_price_to_tour', 10, 2)->nullable()->after('transfer_gaz66')->comment('Вартість трансферу в тур');
            $table->decimal('transfer_price_from_tour', 10, 2)->nullable()->after('transfer_price_to_tour')->comment('Вартість трансферу з туру');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['transfer_price_to_tour', 'transfer_price_from_tour']);
        });
    }
};

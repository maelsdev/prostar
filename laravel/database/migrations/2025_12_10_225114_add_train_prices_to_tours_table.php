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
            $table->decimal('train_price_to', 10, 2)->nullable()->after('transfer_price_from_tour')->comment('Вартість проїзду потягом туди');
            $table->decimal('train_price_from', 10, 2)->nullable()->after('train_price_to')->comment('Вартість проїзду потягом назад');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['train_price_to', 'train_price_from']);
        });
    }
};

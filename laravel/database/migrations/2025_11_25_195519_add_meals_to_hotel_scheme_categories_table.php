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
        Schema::table('hotel_scheme_categories', function (Blueprint $table) {
            $table->string('meals')->default('no_meals')->after('price_type')->comment('Тип харчування: breakfast, breakfast_dinner, no_meals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_scheme_categories', function (Blueprint $table) {
            $table->dropColumn('meals');
        });
    }
};

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
            $table->string('hotel_name')->nullable()->after('days_on_resort');
            $table->text('hotel_description')->nullable()->after('hotel_name');
            $table->boolean('meals_breakfast')->default(false)->after('hotel_description');
            $table->boolean('meals_dinner')->default(false)->after('meals_breakfast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'hotel_name',
                'hotel_description',
                'meals_breakfast',
                'meals_dinner',
            ]);
        });
    }
};

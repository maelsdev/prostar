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
            $table->unsignedBigInteger('calculator_hotel_id')->nullable()->after('calculator_people_count')->comment('ID готелю для калькулятора');
            $table->json('hotel_options')->nullable()->after('calculator_hotel_id')->comment('Опції номерів готелю з цінами');
            
            $table->foreign('calculator_hotel_id')->references('id')->on('hotels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropForeign(['calculator_hotel_id']);
            $table->dropColumn(['calculator_hotel_id', 'hotel_options']);
        });
    }
};

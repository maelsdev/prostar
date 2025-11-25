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
            $table->time('departure_time')->nullable()->after('start_date');
            $table->time('arrival_time')->nullable()->after('end_date');
            $table->integer('nights_in_road')->nullable()->after('arrival_time');
            $table->integer('nights_in_hotel')->nullable()->after('nights_in_road');
            $table->integer('days_on_resort')->nullable()->after('nights_in_hotel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'departure_time',
                'arrival_time',
                'nights_in_road',
                'nights_in_hotel',
                'days_on_resort',
            ]);
        });
    }
};

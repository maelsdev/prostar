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
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_hotels_section')->default(true)->after('button_action');
            $table->boolean('show_activities_section')->default(true)->after('show_hotels_section');
            $table->boolean('show_about_section_after_tours')->default(true)->after('show_activities_section');
            $table->boolean('show_contact_section')->default(true)->after('show_about_section_after_tours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'show_hotels_section',
                'show_activities_section',
                'show_about_section_after_tours',
                'show_contact_section',
            ]);
        });
    }
};

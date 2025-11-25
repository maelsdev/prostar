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
        Schema::table('hotels', function (Blueprint $table) {
            $table->foreignId('scheme_image_id')->nullable()->after('name')->constrained('media_files')->onDelete('set null');
            $table->text('scheme_description')->nullable()->after('scheme_image_id')->comment('Опис схеми готелю');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropForeign(['scheme_image_id']);
            $table->dropColumn(['scheme_image_id', 'scheme_description']);
        });
    }
};

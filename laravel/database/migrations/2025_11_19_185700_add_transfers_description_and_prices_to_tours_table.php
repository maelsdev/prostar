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
            // Трансфери
            $table->boolean('transfer_train')->default(false)->after('main_image_id');
            $table->boolean('transfer_bus')->default(false)->after('transfer_train');
            $table->boolean('transfer_plane')->default(false)->after('transfer_bus');
            $table->boolean('transfer_taxi')->default(false)->after('transfer_plane');
            
            // Опис
            $table->text('short_description')->nullable()->after('transfer_taxi');
            $table->text('full_description')->nullable()->after('short_description');
            
            // Варіанти ціни (JSON для зберігання масиву варіантів)
            $table->json('price_options')->nullable()->after('full_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_train',
                'transfer_bus',
                'transfer_plane',
                'transfer_taxi',
                'short_description',
                'full_description',
                'price_options',
            ]);
        });
    }
};

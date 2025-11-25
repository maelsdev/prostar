<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable()->default('+38(098) 12-12-011');
            $table->string('telegram_phone')->nullable()->default('380981212011');
            $table->string('whatsapp_phone')->nullable()->default('380981212011');
            $table->string('telegram_username')->nullable()->default('pro_s_tar');
            $table->boolean('show_language_switcher')->default(true);
            $table->json('weather_resorts')->nullable(); // Масив курортів для погоди
            $table->string('default_weather_resort')->nullable()->default('dragobrat');
            $table->timestamps();
        });

        // Створити один запис з дефолтними значеннями
        DB::table('settings')->insert([
            'phone' => '+38(098) 12-12-011',
            'telegram_phone' => '380981212011',
            'whatsapp_phone' => '380981212011',
            'telegram_username' => 'pro_s_tar',
            'show_language_switcher' => true,
            'weather_resorts' => json_encode([
                ['value' => 'dragobrat', 'label' => 'Драгобрат'],
                ['value' => 'bukovel', 'label' => 'Буковель'],
                ['value' => 'slavske', 'label' => 'Славське'],
                ['value' => 'pylypets', 'label' => 'Пилипець'],
            ]),
            'default_weather_resort' => 'dragobrat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

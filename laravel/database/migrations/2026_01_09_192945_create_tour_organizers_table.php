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
        Schema::create('tour_organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->string('name')->comment('Ім\'я організатора');
            $table->string('phone')->nullable()->comment('Номер телефону');
            $table->string('telegram_username')->nullable()->comment('Нік в Telegram');
            $table->integer('sort_order')->default(0)->comment('Порядок сортування');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_organizers');
    }
};

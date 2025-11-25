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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('room_type')->comment('Тип номера (назва)');
            $table->json('bed_types')->comment('Типи ліжок: одномісне, двоспальне (може бути кілька)');
            $table->tinyInteger('rooms_count')->default(1)->comment('Кількість кімнат: 1 або 2');
            $table->string('meals')->default('no_meals')->comment('Харчування: breakfast, breakfast_dinner, no_meals');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

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
        Schema::create('hotel_scheme_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('hotel_scheme_categories')->onDelete('cascade');
            $table->string('room_number')->comment('Номер кімнати');
            $table->enum('meals', ['breakfast', 'breakfast_dinner', 'no_meals'])->default('no_meals')->comment('Тип харчування');
            $table->decimal('price', 10, 2)->comment('Ціна');
            $table->string('first_name')->nullable()->comment('Ім\'я (якщо ціна за номер)');
            $table->string('last_name')->nullable()->comment('Прізвище (якщо ціна за номер)');
            $table->integer('sort_order')->default(0)->comment('Порядок сортування');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_scheme_items');
    }
};

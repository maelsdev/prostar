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
        Schema::create('hotel_scheme_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('name')->comment('Назва категорії (тип номера)');
            $table->enum('price_type', ['per_room', 'per_place'])->default('per_place')->comment('Тип ціни: за номер або за місце (завжди per_place)');
            $table->integer('rooms_count')->default(1)->comment('Кількість кімнат у категорії');
            $table->timestamps();
            
            // Унікальність: один номер може бути тільки в одній категорії
            $table->unique('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_scheme_categories');
    }
};

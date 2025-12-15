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
        Schema::create('crm_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_table_id')->constrained('crm_tables')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('name')->comment('Назва категорії (тип номера)');
            $table->enum('price_type', ['per_room', 'per_place'])->default('per_place')->comment('Тип ціни: за номер або за місце');
            $table->integer('rooms_count')->default(1)->comment('Кількість кімнат у категорії');
            $table->integer('sort_order')->default(0)->comment('Порядок сортування');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_categories');
    }
};

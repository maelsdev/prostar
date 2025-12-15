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
        Schema::create('crm_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_category_id')->constrained('crm_categories')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable()->after('crm_category_id')->comment('ID батьківського номера (якщо це місце)');
            $table->integer('place_number')->nullable()->after('parent_id')->comment('Номер місця в номері (1, 2, 3...)');
            $table->boolean('is_parent')->default(false)->after('place_number')->comment('Чи є це батьківським номером');
            $table->string('room_number')->comment('Номер кімнати');
            $table->enum('meals', ['breakfast', 'breakfast_dinner', 'no_meals'])->default('no_meals')->comment('Тип харчування');
            $table->decimal('price', 10, 2)->comment('Ціна');
            $table->string('first_name')->nullable()->comment('Ім\'я');
            $table->string('last_name')->nullable()->comment('Прізвище');
            $table->string('phone')->nullable()->comment('Телефон');
            $table->string('telegram')->nullable()->comment('Телеграм nickname');
            $table->decimal('advance', 10, 2)->default(0)->comment('Аванс');
            $table->decimal('balance', 10, 2)->default(0)->comment('Залишок (вартість - аванс)');
            $table->boolean('has_transfer_there')->default(false)->comment('Трансфер туди');
            $table->boolean('has_transfer_back')->default(false)->comment('Трансфер назад');
            $table->text('info')->nullable()->comment('Інформація');
            $table->integer('sort_order')->default(0)->comment('Порядок сортування');
            $table->timestamps();
            
            // Додаємо foreign key для parent_id окремо
            $table->foreign('parent_id')->references('id')->on('crm_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_items');
    }
};

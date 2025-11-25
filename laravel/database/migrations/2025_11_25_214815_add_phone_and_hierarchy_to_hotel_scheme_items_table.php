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
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('category_id')->comment('ID батьківського номера (якщо це місце)');
            $table->integer('place_number')->nullable()->after('parent_id')->comment('Номер місця в номері (1, 2, 3...)');
            $table->boolean('is_parent')->default(false)->after('place_number')->comment('Чи є це батьківським номером (містить тільки room_number)');
            $table->string('phone')->nullable()->after('last_name')->comment('Телефон');
        });
        
        // Додаємо foreign key окремо, щоб уникнути проблем з циркулярними залежностями
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('hotel_scheme_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_scheme_items', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'place_number', 'is_parent', 'phone']);
        });
    }
};

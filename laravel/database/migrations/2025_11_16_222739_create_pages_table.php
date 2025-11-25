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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // 'home', 'about', etc.
            $table->string('name'); // 'Головна', 'Про нас', etc.
            $table->string('season')->nullable(); // 'SEASON 2025-2026'
            $table->string('h1')->nullable(); // 'ГІРСЬКОЛИЖНІ ТУРИ'
            $table->text('description')->nullable(); // Опис в редакторі
            $table->string('button_text')->nullable(); // 'Обрати тур'
            $table->string('button_action')->nullable(); // '#tours' для скролу
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};

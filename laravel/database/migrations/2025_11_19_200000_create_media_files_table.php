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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['file', 'folder'])->default('file');
            $table->string('path')->nullable(); // Шлях до файлу на диску
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->nullable(); // Розмір файлу в байтах
            $table->timestamps();

            $table->index('folder_id');
            $table->index('type');
        });

        // Додаємо foreign key після створення таблиці
        Schema::table('media_files', function (Blueprint $table) {
            $table->foreign('folder_id')
                  ->references('id')
                  ->on('media_files')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};


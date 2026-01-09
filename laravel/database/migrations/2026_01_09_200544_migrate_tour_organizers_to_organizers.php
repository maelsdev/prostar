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
        // Мігруємо дані з tour_organizers в organizers
        if (Schema::hasTable('tour_organizers')) {
            $tourOrganizers = DB::table('tour_organizers')->get();
            
            foreach ($tourOrganizers as $tourOrganizer) {
                // Перевіряємо, чи вже існує організатор з такими даними
                $existingOrganizer = DB::table('organizers')
                    ->where('name', $tourOrganizer->name)
                    ->where('phone', $tourOrganizer->phone)
                    ->where('telegram_username', $tourOrganizer->telegram_username)
                    ->first();
                
                if ($existingOrganizer) {
                    // Якщо організатор вже існує, просто додаємо зв'язок
                    $organizerId = $existingOrganizer->id;
                } else {
                    // Створюємо нового організатора
                    $organizerId = DB::table('organizers')->insertGetId([
                        'name' => $tourOrganizer->name,
                        'phone' => $tourOrganizer->phone,
                        'telegram_username' => $tourOrganizer->telegram_username,
                        'sort_order' => $tourOrganizer->sort_order,
                        'created_at' => $tourOrganizer->created_at,
                        'updated_at' => $tourOrganizer->updated_at,
                    ]);
                }
                
                // Додаємо зв'язок між туром і організатором
                if (Schema::hasTable('organizer_tour')) {
                    DB::table('organizer_tour')->insertOrIgnore([
                        'organizer_id' => $organizerId,
                        'tour_id' => $tourOrganizer->tour_id,
                        'sort_order' => $tourOrganizer->sort_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // При відкаті просто очищаємо зв'язки
        if (Schema::hasTable('organizer_tour')) {
            DB::table('organizer_tour')->truncate();
        }
    }
};

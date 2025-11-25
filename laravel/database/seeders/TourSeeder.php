<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tour;
use Carbon\Carbon;

class TourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tours = [
            [
                'name' => 'Альпійські курорти',
                'resort' => 'Інсбрук, Цель-ам-Зее',
                'country' => 'Австрія',
                'start_date' => Carbon::create(2025, 12, 15),
                'end_date' => Carbon::create(2025, 12, 22),
            ],
            [
                'name' => 'Карпатські траси',
                'resort' => 'Буковель, Драгобрат',
                'country' => 'Україна',
                'start_date' => Carbon::create(2026, 1, 5),
                'end_date' => Carbon::create(2026, 1, 12),
            ],
            [
                'name' => 'Скандинавські курорти',
                'resort' => 'Тромсе, Олесунн',
                'country' => 'Норвегія',
                'start_date' => Carbon::create(2026, 1, 20),
                'end_date' => Carbon::create(2026, 1, 27),
            ],
            [
                'name' => 'Альпійські курорти',
                'resort' => 'Шамони, Курмайор',
                'country' => 'Франція',
                'start_date' => Carbon::create(2026, 2, 10),
                'end_date' => Carbon::create(2026, 2, 17),
            ],
            [
                'name' => 'Карпатські траси',
                'resort' => 'Славське, Пилипець',
                'country' => 'Україна',
                'start_date' => Carbon::create(2026, 2, 25),
                'end_date' => Carbon::create(2026, 3, 4),
            ],
        ];

        foreach ($tours as $tour) {
            Tour::create($tour);
        }
    }
}

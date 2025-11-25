<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Оновлювати погоду щодня о 8:00 ранку
        $schedule->command('weather:update')
            ->dailyAt('08:00')
            ->timezone('Europe/Kiev')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Помилка автоматичного оновлення погоди');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

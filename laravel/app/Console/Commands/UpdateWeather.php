<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class UpdateWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Оновлює погоду для Драгобрату';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Оновлення погоди...');
        
        // Отримуємо свіжі дані з бази
        $settings = Setting::first();
        $weatherController = new \App\Http\Controllers\WeatherController();
        
        try {
            // Очистити кеш обох джерел перед оновленням
            Cache::forget('weather_dragobrat_weatherapi');
            Cache::forget('weather_dragobrat_snih_info');
            
            $weatherSource = $settings->weather_source ?? 'weatherapi';
            
            if ($weatherSource === 'snih_info') {
                $result = $weatherController->getWeatherFromSnihInfo($settings);
            } else {
                $result = $weatherController->getWeatherFromWeatherAPI($settings);
            }
            
            if (isset($result['error'])) {
                $this->error('Помилка: ' . $result['error']);
                return Command::FAILURE;
            }
            
            // Зберегти в кеш на 4 години (14400 секунд)
            Cache::put('weather_dragobrat_' . $weatherSource, $result, 14400);
            
            $this->info('Погода успішно оновлена!');
            $this->info('Джерело: ' . ($weatherSource === 'snih_info' ? 'snih.info' : 'WeatherAPI.com'));
            $this->info('Температура: ' . $result['temp']);
            $this->info('Вітер: ' . $result['wind']);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Помилка з\'єднання: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

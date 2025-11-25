<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
        
        $settings = Setting::getSettings();
        $apiKey = $settings->weatherapi_key;
        
        if (!$apiKey) {
            $this->error('API ключ не налаштовано');
            return Command::FAILURE;
        }
        
        try {
            // Очистити кеш
            Cache::forget('weather_dragobrat');
            
            // WeatherAPI.com використовує координати lat,lon
            $url = "https://api.weatherapi.com/v1/current.json";
            $response = Http::timeout(5)->get($url, [
                'key' => $apiKey,
                'q' => '48.2636,24.2394',
                'lang' => 'uk'
            ]);
            
            // Отримати інформацію про ліміти з headers
            $headers = $response->headers();
            $requestsRemaining = null;
            
            if (isset($headers['x-weatherapi-qpm-left'])) {
                $requestsRemaining = (int) $headers['x-weatherapi-qpm-left'][0];
            } elseif (isset($headers['X-Weatherapi-Qpm-Left'])) {
                $requestsRemaining = (int) $headers['X-Weatherapi-Qpm-Left'][0];
            }
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Оновити статистику в базі даних
                $settings->weather_last_updated = now();
                if ($requestsRemaining !== null) {
                    $settings->weather_requests_remaining = $requestsRemaining;
                }
                $settings->save();
                
                // Зберегти в кеш
                Cache::put('weather_dragobrat', [
                    'temp' => round($data['current']['temp_c']) . '°C',
                    'wind' => round($data['current']['wind_kph'] / 3.6) . ' м/с',
                    'icon' => 'https:' . ($data['current']['condition']['icon'] ?? ''),
                    'description' => $data['current']['condition']['text'] ?? null
                ], 600);
                
                $this->info('Погода успішно оновлена!');
                $this->info('Температура: ' . round($data['current']['temp_c']) . '°C');
                $this->info('Вітер: ' . round($data['current']['wind_kph'] / 3.6) . ' м/с');
                if ($requestsRemaining !== null) {
                    $this->info('Залишилось запитів: ' . $requestsRemaining);
                }
                
                return Command::SUCCESS;
            }
            
            $this->error('Помилка отримання даних з API');
            return Command::FAILURE;
            
        } catch (\Exception $e) {
            $this->error('Помилка з\'єднання: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class WeatherController extends Controller
{
    // Координати Драгобрату
    private const DRAGOBRAT_COORDS = [
        'lat' => 48.2636,
        'lon' => 24.2394
    ];

    public function getWeather()
    {
        // Отримуємо свіжі дані з бази, щоб уникнути проблем з кешем моделі
        $settings = Setting::first();
        $weatherSource = $settings->weather_source ?? 'weatherapi';
        
        $cacheKey = 'weather_dragobrat_' . $weatherSource;
        
        // Кеш на 4 години (14400 секунд) - оновлюється при завантаженні сторінки
        return Cache::remember($cacheKey, 14400, function () use ($weatherSource, $settings) {
            if ($weatherSource === 'snih_info') {
                return $this->getWeatherFromSnihInfo($settings);
            } else {
                return $this->getWeatherFromWeatherAPI($settings);
            }
        });
    }
    
    /**
     * Отримати погоду з WeatherAPI.com
     */
    public function getWeatherFromWeatherAPI($settings)
    {
        $apiKey = $settings->weatherapi_key;
        
        if (!$apiKey) {
            return [
                'temp' => '--°C',
                'wind' => '-- м/с',
                'humidity' => '--%',
                'error' => 'API ключ не налаштовано'
            ];
        }
        
        try {
            $url = "https://api.weatherapi.com/v1/current.json";
            $response = Http::timeout(5)->get($url, [
                'key' => $apiKey,
                'q' => self::DRAGOBRAT_COORDS['lat'] . ',' . self::DRAGOBRAT_COORDS['lon'],
                'lang' => 'uk'
            ]);
            
            $headers = $response->headers();
            $requestsRemaining = null;
            
            if (isset($headers['x-weatherapi-qpm-left'])) {
                $requestsRemaining = (int) $headers['x-weatherapi-qpm-left'][0];
            } elseif (isset($headers['X-Weatherapi-Qpm-Left'])) {
                $requestsRemaining = (int) $headers['X-Weatherapi-Qpm-Left'][0];
            }
            
            if ($response->successful()) {
                $data = $response->json();
                
                $settings = Setting::first();
                if ($settings) {
                    $settings->weather_last_updated = now();
                    if ($requestsRemaining !== null) {
                        $settings->weather_requests_remaining = $requestsRemaining;
                    }
                    $settings->save();
                }
                
                return [
                    'temp' => round($data['current']['temp_c']) . '°C',
                    'wind' => round($data['current']['wind_kph'] / 3.6) . ' м/с',
                    'humidity' => round($data['current']['humidity']) . '%',
                    'icon' => 'https:' . ($data['current']['condition']['icon'] ?? ''),
                    'description' => $data['current']['condition']['text'] ?? null
                ];
            }
            
            return [
                'temp' => '--°C',
                'wind' => '-- м/с',
                'humidity' => '--%',
                'error' => 'Помилка отримання даних'
            ];
        } catch (\Exception $e) {
            return [
                'temp' => '--°C',
                'wind' => '-- м/с',
                'humidity' => '--%',
                'error' => 'Помилка з\'єднання'
            ];
        }
    }
    
    /**
     * Отримати погоду з snih.info (метеостанція Davis)
     */
    public function getWeatherFromSnihInfo($settings)
    {
        try {
            $url = "https://snih.info/api/davis/?id=1";
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Логування для діагностики
                \Log::info('Snih.info API full response', ['data' => $data]);
                
                if (isset($data['data']['conditions'][0])) {
                    $conditions = $data['data']['conditions'][0];
                    
                    // Логування доступних полів
                    \Log::info('Snih.info conditions fields', ['fields' => array_keys($conditions), 'values' => $conditions]);
                    
                    // Температура вже в Цельсіях (не потрібна конвертація з Фаренгейта)
                    $tempC = round($conditions['temp'] ?? 0);
                    
                    // Конвертація швидкості вітру з mph в м/с
                    // Використовуємо середню швидкість за 10 хвилин
                    $windMph = $conditions['wind_speed_avg_last_10_min'] ?? $conditions['wind_speed_last'] ?? null;
                    $windMs = $windMph !== null ? round($windMph * 0.44704, 1) : null;
                    
                    // Вологість
                    $humidity = isset($conditions['hum']) ? round($conditions['hum']) : null;
                    
                    // Якщо вітер або вологість відсутні (null або 0), використовуємо WeatherAPI як fallback
                    if (($windMs === null || $windMs == 0 || $humidity === null || $humidity == 0) && $settings->weatherapi_key) {
                        \Log::info('Snih.info missing wind/humidity, using WeatherAPI fallback', [
                            'windMs' => $windMs,
                            'humidity' => $humidity
                        ]);
                        $weatherApiData = $this->getWeatherFromWeatherAPI($settings);
                        
                        // Перевіряємо, чи отримали дані з WeatherAPI
                        if (!isset($weatherApiData['error'])) {
                            // Використовуємо температуру з snih.info, але вітер та вологість з WeatherAPI
                            $result = [
                                'temp' => $tempC . '°C',
                                'wind' => $weatherApiData['wind'] ?? '-- м/с',
                                'humidity' => $weatherApiData['humidity'] ?? '--%',
                                'icon' => $weatherApiData['icon'] ?? null,
                                'description' => $weatherApiData['description'] ?? null,
                                'source' => 'snih.info (temp) + WeatherAPI (wind/humidity)'
                            ];
                            
                            // Оновити статистику
                            $settings = Setting::first();
                            if ($settings) {
                                $settings->weather_last_updated = now();
                                $settings->save();
                            }
                            
                            return $result;
                        } else {
                            \Log::warning('WeatherAPI fallback failed', ['error' => $weatherApiData['error'] ?? 'unknown']);
                        }
                    }
                    
                    // Оновити статистику
                    $settings = Setting::first();
                    if ($settings) {
                        $settings->weather_last_updated = now();
                        $settings->save();
                    }
                    
                    return [
                        'temp' => $tempC . '°C',
                        'wind' => ($windMs !== null && $windMs > 0) ? $windMs . ' м/с' : '-- м/с',
                        'humidity' => ($humidity !== null && $humidity > 0) ? $humidity . '%' : '--%',
                        'icon' => null,
                        'description' => null,
                        'source' => 'snih.info'
                    ];
                }
            }
            
            return [
                'temp' => '--°C',
                'wind' => '-- м/с',
                'humidity' => '--%',
                'error' => 'Помилка отримання даних'
            ];
        } catch (\Exception $e) {
            \Log::error('Snih.info API error', ['error' => $e->getMessage()]);
            return [
                'temp' => '--°C',
                'wind' => '-- м/с',
                'humidity' => '--%',
                'error' => 'Помилка з\'єднання: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Примусове оновлення погоди (без кешу)
     */
    public function forceUpdate()
    {
        // Отримуємо свіжі дані з бази
        $settings = Setting::first();
        $weatherSource = $settings->weather_source ?? 'weatherapi';
        
        try {
            // Очистити кеш обох джерел перед примусовим оновленням
            Cache::forget('weather_dragobrat_weatherapi');
            Cache::forget('weather_dragobrat_snih_info');
            
            if ($weatherSource === 'snih_info') {
                $result = $this->getWeatherFromSnihInfo($settings);
            } else {
                $result = $this->getWeatherFromWeatherAPI($settings);
            }
            
            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
            
            // Зберегти в кеш на 4 години (14400 секунд)
            Cache::put('weather_dragobrat_' . $weatherSource, $result, 14400);
            
            return response()->json([
                'success' => true,
                'message' => 'Погода успішно оновлена',
                'temp' => $result['temp'],
                'wind' => $result['wind'],
                'humidity' => $result['humidity'] ?? '--%',
                'source' => $weatherSource === 'snih_info' ? 'snih.info' : 'WeatherAPI.com',
                'last_updated' => now()->format('d.m.Y H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Помилка з\'єднання: ' . $e->getMessage()
            ], 500);
        }
    }
}

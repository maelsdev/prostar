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
        $settings = Setting::getSettings();
        $apiKey = $settings->weatherapi_key;
        
        if (!$apiKey) {
            return response()->json([
                'temp' => '--°C',
                'wind' => '-- м/с',
                'error' => 'API ключ не налаштовано'
            ]);
        }
        
        $cacheKey = 'weather_dragobrat';
        
        return Cache::remember($cacheKey, 600, function () use ($apiKey) {
            try {
                // WeatherAPI.com використовує координати lat,lon
                $url = "https://api.weatherapi.com/v1/current.json";
                $response = Http::timeout(5)->get($url, [
                    'key' => $apiKey,
                    'q' => self::DRAGOBRAT_COORDS['lat'] . ',' . self::DRAGOBRAT_COORDS['lon'],
                    'lang' => 'uk'
                ]);
                
                // Отримати інформацію про ліміти з headers
                $headers = $response->headers();
                $requestsRemaining = null;
                
                // WeatherAPI.com повертає інформацію про ліміти в header x-weatherapi-qpm-left
                if (isset($headers['x-weatherapi-qpm-left'])) {
                    $requestsRemaining = (int) $headers['x-weatherapi-qpm-left'][0];
                } elseif (isset($headers['X-Weatherapi-Qpm-Left'])) {
                    $requestsRemaining = (int) $headers['X-Weatherapi-Qpm-Left'][0];
                }
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Оновити статистику в базі даних (поза кешем)
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
                        'wind' => round($data['current']['wind_kph'] / 3.6) . ' м/с', // Конвертація з км/год в м/с
                        'icon' => 'https:' . ($data['current']['condition']['icon'] ?? ''),
                        'description' => $data['current']['condition']['text'] ?? null
                    ];
                }
                
                return [
                    'temp' => '--°C',
                    'wind' => '-- м/с',
                    'error' => 'Помилка отримання даних'
                ];
            } catch (\Exception $e) {
                return [
                    'temp' => '--°C',
                    'wind' => '-- м/с',
                    'error' => 'Помилка з\'єднання'
                ];
            }
        });
    }
    
    /**
     * Примусове оновлення погоди (без кешу)
     */
    public function forceUpdate()
    {
        $settings = Setting::getSettings();
        $apiKey = $settings->weatherapi_key;
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API ключ не налаштовано'
            ], 400);
        }
        
        try {
            // Очистити кеш
            Cache::forget('weather_dragobrat');
            
            // WeatherAPI.com використовує координати lat,lon
            $url = "https://api.weatherapi.com/v1/current.json";
            $response = Http::timeout(5)->get($url, [
                'key' => $apiKey,
                'q' => self::DRAGOBRAT_COORDS['lat'] . ',' . self::DRAGOBRAT_COORDS['lon'],
                'lang' => 'uk'
            ]);
            
            // Отримати інформацію про ліміти з headers
            $headers = $response->headers();
            $requestsRemaining = null;
            
            // WeatherAPI.com повертає інформацію про ліміти в header x-weatherapi-qpm-left
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
                
                return response()->json([
                    'success' => true,
                    'message' => 'Погода успішно оновлена',
                    'temp' => round($data['current']['temp_c']) . '°C',
                    'wind' => round($data['current']['wind_kph'] / 3.6) . ' м/с',
                    'requests_remaining' => $requestsRemaining,
                    'last_updated' => now()->format('d.m.Y H:i:s')
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Помилка отримання даних з API'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Помилка з\'єднання: ' . $e->getMessage()
            ], 500);
        }
    }
}

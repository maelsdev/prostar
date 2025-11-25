<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EditSettings extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
    
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }
    
    public function getForceUpdateWeatherAction()
    {
        return Actions\Action::make('forceUpdateWeather')
            ->label('Оновити погоду')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Примусове оновлення погоди')
            ->modalDescription('Це оновить дані погоди та статистику API. Продовжити?')
            ->modalSubmitActionLabel('Оновити')
            ->action(function () {
                $settings = Setting::getSettings();
                $apiKey = $settings->weatherapi_key;
                
                if (!$apiKey) {
                    \Filament\Notifications\Notification::make()
                        ->title('Помилка')
                        ->body('API ключ не налаштовано')
                        ->danger()
                        ->send();
                    return;
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
                        
                        // Оновити форму
                        $this->form->fill($settings->fresh()->toArray());
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Успішно')
                            ->body('Погода оновлена. Температура: ' . round($data['current']['temp_c']) . '°C')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Помилка')
                            ->body('Не вдалося отримати дані з API')
                            ->danger()
                            ->send();
                    }
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Помилка')
                        ->body('Помилка з\'єднання: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
    
    public function mount(int | string $record = null): void
    {
        // Завжди використовувати перший запис або створити новий
        $this->record = Setting::getSettings();
        $this->form->fill($this->record->toArray());
    }
    
    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Оновити існуючий запис
        $settings = Setting::first();
        
        if ($settings) {
            $settings->update($data);
            return $settings;
        }
        
        return Setting::create($data);
    }
    
    public function getTitle(): string
    {
        return 'Налаштування';
    }
    
    /**
     * Метод для примусового оновлення погоди
     */
    public function forceUpdateWeather()
    {
        $settings = Setting::getSettings();
        $apiKey = $settings->weatherapi_key;
        
        if (!$apiKey) {
            \Filament\Notifications\Notification::make()
                ->title('Помилка')
                ->body('API ключ не налаштовано')
                ->danger()
                ->send();
            return;
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
                
                // Оновити форму
                $this->record = $settings->fresh();
                $this->form->fill($this->record->toArray());
                
                \Filament\Notifications\Notification::make()
                    ->title('Успішно')
                    ->body('Погода оновлена. Температура: ' . round($data['current']['temp_c']) . '°C')
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Помилка')
                    ->body('Не вдалося отримати дані з API')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Помилка')
                ->body('Помилка з\'єднання: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}


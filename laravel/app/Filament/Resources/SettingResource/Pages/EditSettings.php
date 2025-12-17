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
                // Отримуємо свіжі дані з бази
                $settings = Setting::first();
                $weatherController = new \App\Http\Controllers\WeatherController();
                
                try {
                    // Очистити кеш
                    Cache::forget('weather_dragobrat_weatherapi');
                    Cache::forget('weather_dragobrat_snih_info');
                    
                    $weatherSource = $settings->weather_source ?? 'weatherapi';
                    
                    if ($weatherSource === 'snih_info') {
                        $result = $weatherController->getWeatherFromSnihInfo($settings);
                    } else {
                        if (!$settings->weatherapi_key) {
                            \Filament\Notifications\Notification::make()
                                ->title('Помилка')
                                ->body('API ключ не налаштовано')
                                ->danger()
                                ->send();
                            return;
                        }
                        $result = $weatherController->getWeatherFromWeatherAPI($settings);
                    }
                    
                    if (isset($result['error'])) {
                        \Filament\Notifications\Notification::make()
                            ->title('Помилка')
                            ->body($result['error'])
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    // Зберегти в кеш на 4 години (14400 секунд)
                    Cache::put('weather_dragobrat_' . $weatherSource, $result, 14400);
                    
                    // Оновити форму
                    $this->form->fill($settings->fresh()->toArray());
                    
                    $sourceName = $weatherSource === 'snih_info' ? 'snih.info' : 'WeatherAPI.com';
                    \Filament\Notifications\Notification::make()
                        ->title('Успішно')
                        ->body('Погода оновлена з ' . $sourceName . '. Температура: ' . $result['temp'])
                        ->success()
                        ->send();
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
        $this->record = Setting::first() ?? Setting::getSettings();
        $this->form->fill($this->record->toArray());
    }
    
    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Оновити існуючий запис
        $settings = Setting::first();
        
        if ($settings) {
            // Очистити кеш погоди перед оновленням, якщо змінюється джерело
            if (isset($data['weather_source']) && $settings->weather_source !== $data['weather_source']) {
                Cache::forget('weather_dragobrat_weatherapi');
                Cache::forget('weather_dragobrat_snih_info');
            }
            
            $settings->update($data);
            
            // Оновити запис, щоб отримати свіжі дані
            $this->record = $settings->fresh();
            $this->form->fill($this->record->toArray());
            
            return $this->record;
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
        // Отримуємо свіжі дані з бази
        $settings = Setting::first();
        $weatherController = new \App\Http\Controllers\WeatherController();
        
        try {
            // Очистити кеш
            Cache::forget('weather_dragobrat_weatherapi');
            Cache::forget('weather_dragobrat_snih_info');
            
            $weatherSource = $settings->weather_source ?? 'weatherapi';
            
            if ($weatherSource === 'snih_info') {
                $result = $weatherController->getWeatherFromSnihInfo($settings);
            } else {
                if (!$settings->weatherapi_key) {
                    \Filament\Notifications\Notification::make()
                        ->title('Помилка')
                        ->body('API ключ не налаштовано')
                        ->danger()
                        ->send();
                    return;
                }
                $result = $weatherController->getWeatherFromWeatherAPI($settings);
            }
            
            if (isset($result['error'])) {
                \Filament\Notifications\Notification::make()
                    ->title('Помилка')
                    ->body($result['error'])
                    ->danger()
                    ->send();
                return;
            }
            
            // Зберегти в кеш на 4 години (14400 секунд)
            Cache::put('weather_dragobrat_' . $weatherSource, $result, 14400);
            
            // Оновити форму
            $this->record = $settings->fresh();
            $this->form->fill($this->record->toArray());
            
            $sourceName = $weatherSource === 'snih_info' ? 'snih.info' : 'WeatherAPI.com';
            \Filament\Notifications\Notification::make()
                ->title('Успішно')
                ->body('Погода оновлена з ' . $sourceName . '. Температура: ' . $result['temp'])
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Помилка')
                ->body('Помилка з\'єднання: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}


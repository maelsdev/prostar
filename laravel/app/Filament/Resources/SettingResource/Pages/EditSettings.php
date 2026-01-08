<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use App\Http\Controllers\WeatherController;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
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
                try {
                    $controller = new WeatherController();
                    $response = $controller->forceUpdate();
                    $data = json_decode($response->getContent(), true);
                    
                    if ($data['success'] ?? false) {
                        // Оновити форму
                        $settings = Setting::first();
                        if ($settings) {
                            $this->form->fill($settings->fresh()->toArray());
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Успішно')
                            ->body('Погода оновлена. ' . ($data['message'] ?? ''))
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Помилка')
                            ->body($data['error'] ?? 'Не вдалося оновити погоду')
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
        try {
            $controller = new WeatherController();
            $response = $controller->forceUpdate();
            $data = json_decode($response->getContent(), true);
            
            if ($data['success'] ?? false) {
                // Оновити форму
                $settings = Setting::first();
                if ($settings) {
                    $this->record = $settings->fresh();
                    $this->form->fill($this->record->toArray());
                }
                
                \Filament\Notifications\Notification::make()
                    ->title('Успішно')
                    ->body('Погода оновлена. ' . ($data['message'] ?? ''))
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Помилка')
                    ->body($data['error'] ?? 'Не вдалося оновити погоду')
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


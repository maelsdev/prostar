<?php

namespace App\Filament\Resources\HotelResource\Pages;

use App\Filament\Resources\HotelResource;
use App\Models\Room;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;
    
    protected function afterCreate(): void
    {
        // Після створення готелю, оновлюємо hotel_id для всіх кімнат, які були додані
        $hotel = $this->record;
        
        if (!$hotel || !$hotel->id) {
            return;
        }
        
        // Оновлюємо hotel_id для всіх кімнат цього готелю, які ще не мають hotel_id
        Room::where('hotel_id', null)
            ->orWhere('hotel_id', 0)
            ->update(['hotel_id' => $hotel->id]);
    }
    
    public function getSchemeCategories()
    {
        // При створенні нового готелю категорій ще немає
        return [];
    }
    
    public function generateScheme(): void
    {
        // При створенні нового готелю схему не можна згенерувати
        \Filament\Notifications\Notification::make()
            ->title('Помилка')
            ->body('Спочатку збережіть готель, а потім згенеруйте схему')
            ->warning()
            ->send();
    }
    
    public function refreshScheme(): void
    {
        // При створенні нового готелю схему не можна оновити
        \Filament\Notifications\Notification::make()
            ->title('Помилка')
            ->body('Спочатку збережіть готель')
            ->warning()
            ->send();
    }
}

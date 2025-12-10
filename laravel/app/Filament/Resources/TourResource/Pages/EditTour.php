<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\Hotel;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function importHotelRooms($hotelId): void
    {
        if (!$hotelId) {
            Notification::make()
                ->title('Помилка')
                ->body('Будь ласка, оберіть готель')
                ->danger()
                ->send();
            return;
        }

        $hotel = Hotel::with('rooms')->find($hotelId);
        
        if (!$hotel) {
            Notification::make()
                ->title('Помилка')
                ->body('Готель не знайдено')
                ->danger()
                ->send();
            return;
        }

        $rooms = $hotel->rooms;
        
        if ($rooms->isEmpty()) {
            Notification::make()
                ->title('Увага')
                ->body('У вибраного готелю немає номерів')
                ->warning()
                ->send();
            return;
        }

        // Формуємо масив вартостей номерів
        $roomPrices = [];
        foreach ($rooms as $room) {
            $roomPrices[$room->id] = [
                'room_id' => $room->id,
                'room_type' => $room->room_type,
                'bed_types' => $room->bed_types,
                'meals' => $room->meals,
                'places_per_room' => $room->places_per_room,
                'price' => 0, // Початкова вартість
            ];
        }

        // Оновлюємо запис туру
        $this->record->update([
            'hotel_id' => $hotelId,
            'room_prices' => $roomPrices,
        ]);

        // Оновлюємо запис
        $this->record->refresh();
        
        // Оновлюємо форму з актуальними даними
        $formData = $this->record->toArray();
        // Переконаємося, що room_prices це масив
        if (is_string($formData['room_prices'] ?? null)) {
            $formData['room_prices'] = json_decode($formData['room_prices'], true);
        }
        $this->form->fill($formData);

        Notification::make()
            ->title('Успішно')
            ->body('Типи номерів імпортовано. Заповніть вартості проживання.')
            ->success()
            ->send();
    }

    public function clearCalculatorForm(): void
    {
        // Очищаємо поля калькулятора
        $this->record->update([
            'hotel_id' => null,
            'room_prices' => null,
            'transfer_price_to_tour' => null,
            'transfer_price_from_tour' => null,
            'has_transfer_to_tour' => false,
            'has_transfer_from_tour' => false,
        ]);

        // Оновлюємо запис та форму
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('$refresh');

        Notification::make()
            ->title('Успішно')
            ->body('Форма калькулятора очищена')
            ->success()
            ->send();
    }
}

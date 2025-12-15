<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\Hotel;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Завантажити типи номерів готелю при відкритті форми
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['calculator_hotel_id'])) {
            $hotel = Hotel::with('rooms')->find($data['calculator_hotel_id']);
            if ($hotel) {
                // Збережені типи з цінами, маржею та кількістю
                $savedTypes = $data['calculator_room_types'] ?? [];
                $savedData = [];
                foreach ($savedTypes as $savedType) {
                    if (isset($savedType['places'])) {
                        $savedData[$savedType['places']] = [
                            'price_per_place' => $savedType['price_per_place'] ?? 0,
                            'margin' => $savedType['margin'] ?? 0,
                            'quantity' => $savedType['quantity'] ?? 0,
                        ];
                    }
                }
                
                // Групуємо номери за кількістю місць та рахуємо кількість
                $roomTypes = [];
                foreach ($hotel->rooms as $room) {
                    $places = $room->places_per_room;
                    if ($places > 0) {
                        if (!isset($roomTypes[$places])) {
                            $savedPrice = isset($savedData[$places]) ? (float)($savedData[$places]['price_per_place'] ?? 0) : 0;
                            $savedMargin = isset($savedData[$places]) ? (float)($savedData[$places]['margin'] ?? 0) : 0;
                            $roomTypes[$places] = [
                                'places' => $places,
                                'quantity' => 0,
                                'price_per_place' => $savedPrice,
                                'margin' => $savedMargin,
                            ];
                        }
                        // Додаємо кількість номерів цього типу
                        $roomTypes[$places]['quantity'] += ($room->quantity ?? 1);
                    }
                }
                
                // Якщо є збережені дані, використовуємо збережену кількість (якщо вона є)
                foreach ($savedData as $places => $saved) {
                    if (isset($roomTypes[$places]) && isset($saved['quantity']) && $saved['quantity'] > 0) {
                        $roomTypes[$places]['quantity'] = (int)$saved['quantity'];
                    }
                }
                
                // Сортуємо за кількістю місць
                ksort($roomTypes);
                
                $data['calculator_room_types'] = array_values($roomTypes);
            }
        }
        
        return $data;
    }

    /**
     * Обробити дані перед збереженням
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Переконатися, що calculator_room_types має правильні типи даних
        if (isset($data['calculator_room_types']) && is_array($data['calculator_room_types'])) {
            foreach ($data['calculator_room_types'] as &$roomType) {
                if (isset($roomType['places'])) {
                    $roomType['places'] = (int)$roomType['places'];
                }
                if (isset($roomType['quantity'])) {
                    $roomType['quantity'] = (int)$roomType['quantity'];
                }
                if (isset($roomType['price_per_place'])) {
                    $roomType['price_per_place'] = (float)$roomType['price_per_place'];
                }
                if (isset($roomType['margin'])) {
                    $roomType['margin'] = (float)$roomType['margin'];
                }
            }
            unset($roomType);
        }
        
        return $data;
    }
}

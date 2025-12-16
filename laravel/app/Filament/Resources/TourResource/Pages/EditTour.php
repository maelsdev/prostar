<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\Hotel;
use App\Models\CrmItem;
use Filament\Actions;
use Filament\Notifications\Notification;
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

    /**
     * Зберегти дані CRM таблиці
     */
    public function saveCrmItems($categoryId, $rooms): void
    {
        try {
            $category = \App\Models\CrmCategory::find($categoryId);
            if (!$category) {
                Notification::make()
                    ->title('Помилка')
                    ->body('Категорія не знайдена')
                    ->danger()
                    ->send();
                return;
            }

            $sortOrder = 0;
            $savedCount = 0;
            $updatedCount = 0;

            foreach ($rooms as $roomIndex => $roomData) {
                $parentId = $roomData['id'] ?? null;
                $roomNumber = $roomData['room_number'] ?? '';
                $places = $roomData['places'] ?? [];

                // Зберігаємо або створюємо батьківський номер
                // Примітка: room_number не оновлюється, оскільки воно закрите від редагування
                if ($parentId) {
                    $parentItem = CrmItem::find($parentId);
                    if ($parentItem) {
                        // Оновлюємо тільки sort_order, room_number залишається незмінним
                        $parentItem->update([
                            'sort_order' => $sortOrder,
                        ]);
                        $updatedCount++;
                    }
                } else {
                    // Створюємо новий батьківський номер
                    $parentItem = CrmItem::create([
                        'crm_category_id' => $categoryId,
                        'parent_id' => null,
                        'place_number' => null,
                        'is_parent' => true,
                        'room_number' => $roomNumber,
                        'meals' => 'no_meals',
                        'price' => 0,
                        'first_name' => null,
                        'last_name' => null,
                        'phone' => null,
                        'telegram' => null,
                        'advance' => 0,
                        'balance' => 0,
                        'has_transfer_there' => false,
                        'has_transfer_back' => false,
                        'info' => null,
                        'sort_order' => $sortOrder,
                    ]);
                    $savedCount++;
                    $parentId = $parentItem->id;
                }

                $sortOrder++;

                // Зберігаємо або створюємо місця
                foreach ($places as $placeData) {
                    $placeId = $placeData['id'] ?? null;
                    $placeNumber = $placeData['place_number'] ?? 1;

                    $price = (float)($placeData['price'] ?? 0);
                    $advance = (float)($placeData['advance'] ?? 0);
                    $balance = $price - $advance;

                    $placeItemData = [
                        'crm_category_id' => $categoryId,
                        'parent_id' => $parentId,
                        'place_number' => $placeNumber,
                        'is_parent' => false,
                        'room_number' => '',
                        'meals' => $placeData['meals'] ?? 'no_meals',
                        'price' => $price,
                        'first_name' => $placeData['first_name'] ?? '',
                        'last_name' => $placeData['last_name'] ?? '',
                        'phone' => $placeData['phone'] ?? '',
                        'telegram' => $placeData['telegram'] ?? '',
                        'advance' => $advance,
                        'balance' => $balance,
                        'has_transfer_there' => (bool)($placeData['has_transfer_there'] ?? false),
                        'has_transfer_back' => (bool)($placeData['has_transfer_back'] ?? false),
                        'info' => $placeData['info'] ?? '',
                        'sort_order' => $sortOrder,
                    ];

                    if ($placeId) {
                        // Оновлюємо існуюче місце
                        $placeItem = CrmItem::find($placeId);
                        if ($placeItem) {
                            $placeItem->update($placeItemData);
                            $updatedCount++;
                        }
                    } else {
                        // Створюємо нове місце
                        CrmItem::create($placeItemData);
                        $savedCount++;
                    }

                    $sortOrder++;
                }
            }

            Notification::make()
                ->title('Успішно збережено')
                ->body('Оновлено: ' . $updatedCount . ', Створено: ' . $savedCount)
                ->success()
                ->send();

            // Оновлюємо запис
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Помилка збереження')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Підтвердити оплату авансу для конкретного місця
     */
    public function confirmAdvancePayment($placeId, $advanceAmount): void
    {
        try {
            $place = CrmItem::find($placeId);
            if (!$place) {
                Notification::make()
                    ->title('Помилка')
                    ->body('Місце не знайдено')
                    ->danger()
                    ->send();
                return;
            }

            // Оновлюємо аванс та баланс
            $price = (float)($place->price ?? 0);
            $advance = (float)$advanceAmount;
            $balance = $price - $advance;

            $place->update([
                'advance' => $advance,
                'balance' => $balance,
            ]);

            Notification::make()
                ->title('Оплату підтверджено')
                ->body('Аванс у розмірі ' . number_format($advance, 2, '.', ' ') . ' грн збережено')
                ->success()
                ->send();

            // Оновлюємо запис
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Помилка підтвердження оплати')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Зберегти окреме поле місця
     */
    public function savePlaceField($placeId, $fieldName, $fieldValue): void
    {
        try {
            $place = CrmItem::find($placeId);
            if (!$place) {
                Notification::make()
                    ->title('Помилка')
                    ->body('Місце не знайдено')
                    ->danger()
                    ->send();
                return;
            }

            // Валідація поля
            $allowedFields = ['first_name', 'last_name', 'phone', 'telegram', 'price', 'info', 'has_transfer_there', 'has_transfer_back'];
            if (!in_array($fieldName, $allowedFields)) {
                Notification::make()
                    ->title('Помилка')
                    ->body('Невірне ім\'я поля')
                    ->danger()
                    ->send();
                return;
            }

            // Підготовка значення
            $value = $fieldValue;
            if ($fieldName === 'price') {
                $value = (float)$fieldValue;
                // Перераховуємо баланс при зміні ціни
                $advance = (float)($place->advance ?? 0);
                $balance = $value - $advance;
                $place->update([
                    $fieldName => $value,
                    'balance' => $balance,
                ]);
            } elseif (in_array($fieldName, ['has_transfer_there', 'has_transfer_back'])) {
                // Для булевих полів трансферів
                $value = (bool)$fieldValue;
                $place->update([
                    $fieldName => $value,
                ]);
            } else {
                $place->update([
                    $fieldName => $value,
                ]);
            }

            // Оновлюємо запис
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Помилка збереження')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Зберегти всі нефінансові дані місця
     */
    public function savePlaceData($placeId, $data): void
    {
        try {
            $place = CrmItem::find($placeId);
            if (!$place) {
                Notification::make()
                    ->title('Помилка')
                    ->body('Місце не знайдено')
                    ->danger()
                    ->send();
                return;
            }

            // Оновлюємо тільки нефінансові поля
            $place->update([
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'telegram' => $data['telegram'] ?? '',
                'info' => $data['info'] ?? '',
                'has_transfer_there' => (bool)($data['has_transfer_there'] ?? false),
                'has_transfer_back' => (bool)($data['has_transfer_back'] ?? false),
            ]);

            Notification::make()
                ->title('Дані збережено')
                ->body('Всі дані успішно збережено')
                ->success()
                ->send();

            // Оновлюємо запис
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Помилка збереження')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

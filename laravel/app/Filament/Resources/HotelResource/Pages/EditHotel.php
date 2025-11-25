<?php

namespace App\Filament\Resources\HotelResource\Pages;

use App\Filament\Resources\HotelResource;
use App\Models\HotelSchemeCategory;
use App\Models\HotelSchemeItem;
use App\Models\Room;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    public function createCategoryItems($categoryId): void
    {
        $category = HotelSchemeCategory::find($categoryId);
        
        if (!$category) {
            Notification::make()
                ->title('Помилка')
                ->body('Категорія не знайдена')
                ->danger()
                ->send();
            return;
        }
        
        $room = $category->room;
        if (!$room) {
            Notification::make()
                ->title('Помилка')
                ->body('Тип кімнати не знайдено')
                ->danger()
                ->send();
            return;
        }
        
        $roomsCount = $category->rooms_count;
        $meals = $category->meals ?? 'no_meals';
        
        // За місце: кількість = кількість місць × кількість кімнат
        $bedTypes = is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types, true);
        $singleBeds = (int)($bedTypes['single'] ?? 0);
        $doubleBeds = (int)($bedTypes['double'] ?? 0);
        $placesCount = $singleBeds + ($doubleBeds * 2);
        $totalItems = $placesCount * $roomsCount;
        
        // Визначаємо скільки записів потрібно створити
        $itemsToCreate = [];
        $maxOrder = HotelSchemeItem::where('category_id', $categoryId)->max('sort_order') ?? 0;
        
        for ($i = 0; $i < $totalItems; $i++) {
            $itemsToCreate[] = [
                'category_id' => $categoryId,
                'room_number' => '',
                'meals' => $meals,
                'price' => 0,
                'sort_order' => $maxOrder + $i + 1,
            ];
        }
        
        // Створюємо записи
        if (!empty($itemsToCreate)) {
            HotelSchemeItem::insert($itemsToCreate);
            
            Notification::make()
                ->title('Успішно')
                ->body('Створено ' . count($itemsToCreate) . ' записів')
                ->success()
                ->send();
            
            // Оновлюємо форму та перезавантажуємо запис
            $this->record->refresh();
            $this->form->fill($this->record->toArray());
            
            // Оновлюємо Livewire компонент та відправляємо подію
            $this->dispatch('category-items-created');
            $this->dispatch('$refresh');
        } else {
            Notification::make()
                ->title('Помилка')
                ->body('Не вдалося створити записи. Перевірте дані категорії.')
                ->danger()
                ->send();
        }
    }
    
    public function saveRoom($roomId): void
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            Notification::make()
                ->title('Помилка')
                ->body('Кімната не знайдена')
                ->danger()
                ->send();
            return;
        }
        
        // Отримуємо дані з форми для цієї кімнати
        $formData = $this->form->getState();
        $roomsData = $formData['rooms'] ?? [];
        
        // Знаходимо дані для цієї кімнати
        $roomData = null;
        foreach ($roomsData as $index => $roomItem) {
            if (isset($roomItem['id']) && $roomItem['id'] == $roomId) {
                $roomData = $roomItem;
                break;
            }
        }
        
        if (!$roomData) {
            Notification::make()
                ->title('Помилка')
                ->body('Дані кімнати не знайдено')
                ->danger()
                ->send();
            return;
        }
        
        // Формуємо bed_types
        $single = (int)($roomData['bed_single_count'] ?? $roomData['bed_types']['single'] ?? 0);
        $double = (int)($roomData['bed_double_count'] ?? $roomData['bed_types']['double'] ?? 0);
        $roomData['bed_types'] = ['single' => $single, 'double' => $double];
        
        unset($roomData['bed_single_count'], $roomData['bed_double_count'], $roomData['id']);
        
        // Оновлюємо кімнату
        $room->update($roomData);
        
        Notification::make()
            ->title('Успішно')
            ->body('Кімнату збережено')
            ->success()
            ->send();
        
        // Оновлюємо форму
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('$refresh');
    }
    
    public function checkSchemeData(): array
    {
        $hotel = $this->record;
        
        if (!$hotel) {
            return ['has_data' => false, 'items_count' => 0, 'categories_count' => 0];
        }
        
        $existingCategories = HotelSchemeCategory::where('hotel_id', $hotel->id)->get();
        
        if ($existingCategories->isEmpty()) {
            return ['has_data' => false, 'items_count' => 0, 'categories_count' => 0];
        }
        
        $itemsCount = HotelSchemeItem::whereIn('category_id', $existingCategories->pluck('id'))->count();
        
        return [
            'has_data' => $itemsCount > 0,
            'items_count' => $itemsCount,
            'categories_count' => $existingCategories->count(),
        ];
    }
    
    public function generateScheme($confirmed = false): void
    {
        $hotel = $this->record;
        
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
                ->title('Помилка')
                ->body('У готелі немає номерів. Спочатку додайте номери у вкладці "Інформація про готель".')
                ->warning()
                ->send();
            return;
        }
        
        // Перевіряємо, чи є існуючі категорії з даними
        $existingCategories = HotelSchemeCategory::where('hotel_id', $hotel->id)->get();
        $deletedItemsCount = 0;
        
        if ($existingCategories->isNotEmpty()) {
            $deletedItemsCount = HotelSchemeItem::whereIn('category_id', $existingCategories->pluck('id'))->count();
            
            // Якщо є дані і не підтверджено, повертаємо інформацію про необхідність підтвердження
            if ($deletedItemsCount > 0 && !$confirmed) {
                Notification::make()
                    ->title('Увага!')
                    ->body('Увага! Генерація нової схеми видалить всі існуючі дані (' . $deletedItemsCount . ' записів). Підтвердіть дію через підтвердження в браузері.')
                    ->warning()
                    ->persistent()
                    ->send();
                
                return;
            }
            
            // Видаляємо старі категорії (це також видалить всі items через каскадне видалення)
            HotelSchemeCategory::where('hotel_id', $hotel->id)->delete();
        }
        
        // Створюємо нові категорії на основі номерів
        $categoriesCreated = 0;
        $itemsCreated = 0;
        
        foreach ($rooms as $room) {
            $category = HotelSchemeCategory::create([
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'name' => $room->room_type ?? 'Без назви',
                'price_type' => 'per_place',
                'meals' => 'no_meals',
                'rooms_count' => $room->quantity ?? 1,
            ]);
            $categoriesCreated++;
            
            // Розраховуємо кількість місць в номері
            $bedTypes = is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types ?? '{}', true);
            $singleBeds = (int)($bedTypes['single'] ?? 0);
            $doubleBeds = (int)($bedTypes['double'] ?? 0);
            $placesPerRoom = $singleBeds + ($doubleBeds * 2);
            
            // Створюємо батьківські номери та місця для кожного номера
            $roomsCount = $room->quantity ?? 1;
            
            for ($roomIndex = 0; $roomIndex < $roomsCount; $roomIndex++) {
                // Створюємо батьківський номер (містить тільки room_number)
                $parentItem = HotelSchemeItem::create([
                    'category_id' => $category->id,
                    'parent_id' => null,
                    'place_number' => null,
                    'is_parent' => true,
                    'room_number' => '',
                    'meals' => 'no_meals',
                    'price' => 0,
                    'first_name' => null,
                    'last_name' => null,
                    'phone' => null,
                    'telegram' => null,
                    'sort_order' => ($roomIndex * ($placesPerRoom + 1)) + 1,
                ]);
                $itemsCreated++;
                
                // Створюємо місця для цього номера
                for ($placeIndex = 1; $placeIndex <= $placesPerRoom; $placeIndex++) {
                    HotelSchemeItem::create([
                        'category_id' => $category->id,
                        'parent_id' => $parentItem->id,
                        'place_number' => $placeIndex,
                        'is_parent' => false,
                        'room_number' => '',
                        'meals' => $category->meals ?? 'no_meals',
                        'price' => 0,
                        'first_name' => '',
                        'last_name' => '',
                        'phone' => '',
                        'telegram' => '',
                        'advance' => 0,
                        'balance' => 0,
                        'has_transfer_there' => false,
                        'has_transfer_back' => false,
                        'info' => '',
                        'sort_order' => ($roomIndex * ($placesPerRoom + 1)) + 1 + $placeIndex,
                    ]);
                    $itemsCreated++;
                }
            }
        }
        
        $message = 'Створено ' . $categoriesCreated . ' категорій та ' . $itemsCreated . ' записів';
        if ($deletedItemsCount > 0) {
            $message .= '. Видалено ' . $deletedItemsCount . ' старих записів';
        }
        
        Notification::make()
            ->title('Успішно')
            ->body($message)
            ->success()
            ->send();
        
        // Оновлюємо форму та відправляємо подію для оновлення View компонента
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('scheme-generated');
        $this->dispatch('$refresh');
    }
    
    public function refreshScheme(): void
    {
        // Просто оновлюємо форму для перерендерингу таблиці
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('scheme-generated');
        $this->dispatch('$refresh');
        
        Notification::make()
            ->title('Оновлено')
            ->body('Таблицю оновлено')
            ->success()
            ->send();
    }
    
    public function deleteScheme(): void
    {
        $hotel = $this->record;
        
        if (!$hotel) {
            Notification::make()
                ->title('Помилка')
                ->body('Готель не знайдено')
                ->danger()
                ->send();
            return;
        }
        
        // Підраховуємо кількість записів перед видаленням
        $existingCategories = HotelSchemeCategory::where('hotel_id', $hotel->id)->get();
        $itemsCount = 0;
        if ($existingCategories->isNotEmpty()) {
            $itemsCount = HotelSchemeItem::whereIn('category_id', $existingCategories->pluck('id'))->count();
        }
        
        // Видаляємо всі категорії (items видаляться автоматично через каскадне видалення)
        HotelSchemeCategory::where('hotel_id', $hotel->id)->delete();
        
        $message = 'Схему видалено';
        if ($itemsCount > 0) {
            $message .= ' (' . $itemsCount . ' записів)';
        }
        
        Notification::make()
            ->title('Успішно')
            ->body($message)
            ->success()
            ->send();
        
        // Оновлюємо форму та відправляємо подію для оновлення View компонента
        $this->record->refresh();
        $this->form->fill($this->record->toArray());
        $this->dispatch('scheme-deleted');
        $this->dispatch('$refresh');
    }
    
    public function getSchemeCategories()
    {
        $hotel = $this->record;
        if (!$hotel) {
            return [];
        }
        
        $hotel->refresh();
        // Завантажуємо зв'язки з правильним порядком
        $categories = $hotel->schemeCategories()
            ->with(['room', 'items' => function($query) {
                $query->orderBy('sort_order');
            }])
            ->with(['items.places' => function($query) {
                $query->orderBy('place_number');
            }])
            ->orderBy('id')
            ->get();
        
        return $categories->map(function ($category) {
            $room = $category->room;
            $bedTypes = $room ? (is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types ?? '{}', true)) : [];
            $singleBeds = (int)($bedTypes['single'] ?? 0);
            $doubleBeds = (int)($bedTypes['double'] ?? 0);
            $placesPerRoom = $singleBeds + ($doubleBeds * 2);
            $totalPlaces = $placesPerRoom * ($category->rooms_count ?? 1);
            
            // Отримуємо батьківські номери (is_parent = true) - перезавантажуємо зв'язки
            $parentItems = $category->items()
                ->where('is_parent', true)
                ->orderBy('sort_order')
                ->get();
            
            // Створюємо масив батьківських номерів з дочірніми місцями
            $rooms = [];
            $roomsCount = $category->rooms_count ?? 0;
            
            foreach ($parentItems as $index => $parentItem) {
                // Перезавантажуємо дочірні місця для цього батьківського номера
                $parentItem->load('places');
                $places = $parentItem->places->sortBy('place_number')->map(function ($place) {
                    $price = (float)($place->price ?? 0);
                    $advance = (float)($place->advance ?? 0);
                    $balance = $price - $advance;
                    return [
                        'id' => $place->id,
                        'place_number' => $place->place_number,
                        'meals' => $place->meals ?? 'no_meals',
                        'price' => $price,
                        'first_name' => $place->first_name ?? '',
                        'last_name' => $place->last_name ?? '',
                        'phone' => $place->phone ?? '',
                        'telegram' => $place->telegram ?? '',
                        'advance' => $advance,
                        'balance' => $balance,
                        'has_transfer_there' => (bool)($place->has_transfer_there ?? false),
                        'has_transfer_back' => (bool)($place->has_transfer_back ?? false),
                        'info' => $place->info ?? '',
                    ];
                })->toArray();
                
                // Якщо місць немає, створюємо порожні місця
                while (count($places) < $placesPerRoom) {
                    $places[] = [
                        'id' => null,
                        'place_number' => count($places) + 1,
                        'meals' => 'no_meals',
                        'price' => 0,
                        'first_name' => '',
                        'last_name' => '',
                        'phone' => '',
                        'telegram' => '',
                        'advance' => 0,
                        'balance' => 0,
                        'has_transfer_there' => false,
                        'has_transfer_back' => false,
                        'info' => '',
                    ];
                }
                
                $rooms[] = [
                    'id' => $parentItem->id,
                    'room_number' => $parentItem->room_number ?? '',
                    'places' => $places,
                ];
            }
            
            // Якщо батьківських номерів менше, ніж потрібно, додаємо порожні
            while (count($rooms) < $roomsCount) {
                $emptyPlaces = [];
                for ($i = 1; $i <= $placesPerRoom; $i++) {
                    $emptyPlaces[] = [
                        'id' => null,
                        'place_number' => $i,
                        'meals' => 'no_meals',
                        'price' => 0,
                        'first_name' => '',
                        'last_name' => '',
                        'phone' => '',
                        'telegram' => '',
                        'advance' => 0,
                        'balance' => 0,
                        'has_transfer_there' => false,
                        'has_transfer_back' => false,
                        'info' => '',
                    ];
                }
                
                $rooms[] = [
                    'id' => null,
                    'index' => count($rooms) + 1,
                    'room_number' => '',
                    'places' => $emptyPlaces,
                ];
            }
            
            return [
                'id' => $category->id,
                'name' => $category->name,
                'rooms_count' => $category->rooms_count ?? 0,
                'total_places' => $totalPlaces,
                'places_per_room' => $placesPerRoom,
                'rooms' => $rooms,
            ];
        })->toArray();
    }
    
    public function saveSchemeItems($categoryId, $rooms): void
    {
        // Запобігаємо перерендерингу компонента після збереження
        $this->skipRender();
        
        $category = HotelSchemeCategory::find($categoryId);
        
        if (!$category) {
            Notification::make()
                ->title('Помилка')
                ->body('Категорія не знайдена')
                ->danger()
                ->send();
            return;
        }
        
        $room = $category->room;
        $bedTypes = $room ? (is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types ?? '{}', true)) : [];
        $singleBeds = (int)($bedTypes['single'] ?? 0);
        $doubleBeds = (int)($bedTypes['double'] ?? 0);
        $placesPerRoom = $singleBeds + ($doubleBeds * 2);
        
        $meals = $category->meals ?? 'no_meals';
        
        $savedCount = 0;
        $updatedCount = 0;
        $sortOrder = 1;
        
        foreach ($rooms as $roomIndex => $roomData) {
            $parentId = $roomData['id'] ?? null;
            $roomNumber = $roomData['room_number'] ?? '';
            $places = $roomData['places'] ?? [];
            
            // Зберігаємо або створюємо батьківський номер
            if ($parentId) {
                $parentItem = HotelSchemeItem::find($parentId);
                if ($parentItem) {
                    $parentItem->update([
                        'room_number' => $roomNumber,
                        'sort_order' => $sortOrder,
                    ]);
                    $updatedCount++;
                }
            } else {
                // Створюємо новий батьківський номер
                $parentItem = HotelSchemeItem::create([
                    'category_id' => $categoryId,
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
                    'category_id' => $categoryId,
                    'parent_id' => $parentId,
                    'place_number' => $placeNumber,
                    'is_parent' => false,
                    'room_number' => '',
                    'meals' => $placeData['meals'] ?? $meals,
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
                    $placeItem = HotelSchemeItem::find($placeId);
                    if ($placeItem) {
                        $placeItem->update($placeItemData);
                        $updatedCount++;
                    }
                } else {
                    // Створюємо нове місце
                    HotelSchemeItem::create($placeItemData);
                    $savedCount++;
                }
                
                $sortOrder++;
            }
        }
        
        $message = '';
        if ($savedCount > 0 && $updatedCount > 0) {
            $message = 'Збережено: ' . $savedCount . ' нових, оновлено: ' . $updatedCount . ' існуючих';
        } elseif ($savedCount > 0) {
            $message = 'Збережено: ' . $savedCount . ' нових записів';
        } elseif ($updatedCount > 0) {
            $message = 'Оновлено: ' . $updatedCount . ' записів';
        } else {
            $message = 'Немає змін для збереження';
        }
        
        Notification::make()
            ->title('Успішно')
            ->body($message)
            ->success()
            ->send();
        
        // НЕ повертаємо дані - це запобігає оновленню стану Alpine.js та перерендерингу таблиці
        // Дані вже в стані через x-model, тому не потрібно їх оновлювати
    }
}

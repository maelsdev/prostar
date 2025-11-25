@php
    use App\Models\Hotel;
    use App\Models\HotelSchemeCategory;
    
    // Отримуємо категорії через $record або через Livewire
    $categories = [];
    $hotelId = null;
    
    // Спробуємо отримати ID готелю
    if (isset($record)) {
        if (is_object($record) && isset($record->id)) {
            $hotelId = $record->id;
        } elseif (is_numeric($record)) {
            $hotelId = $record;
        } elseif (is_string($record)) {
            // Ігноруємо рядок
        }
    }
    
    // Якщо не отримали ID, спробуємо через Livewire
    if (!$hotelId) {
        try {
            $livewireComponent = \Livewire\Livewire::current();
            if ($livewireComponent && property_exists($livewireComponent, 'record')) {
                $recordObj = $livewireComponent->record;
                if ($recordObj && is_object($recordObj) && isset($recordObj->id)) {
                    $hotelId = $recordObj->id;
                }
            }
        } catch (\Exception $e) {
            // Ігноруємо помилку
        }
    }
    
    if ($hotelId) {
        $hotel = Hotel::with(['schemeCategories.room', 'schemeCategories.items.places'])->find($hotelId);
        if ($hotel) {
            $categories = $hotel->schemeCategories->map(function ($category) {
                $room = $category->room;
                $bedTypes = $room ? (is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types ?? '{}', true)) : [];
                $singleBeds = (int)($bedTypes['single'] ?? 0);
                $doubleBeds = (int)($bedTypes['double'] ?? 0);
                $placesPerRoom = $singleBeds + ($doubleBeds * 2);
                $totalPlaces = $placesPerRoom * ($category->rooms_count ?? 1);
                
                // Отримуємо батьківські номери (is_parent = true)
                $parentItems = $category->items->where('is_parent', true)->sortBy('sort_order')->values();
                
                // Створюємо масив батьківських номерів з дочірніми місцями
                $rooms = [];
                $roomsCount = $category->rooms_count ?? 0;
                
                foreach ($parentItems as $index => $parentItem) {
                    // Отримуємо дочірні місця для цього батьківського номера
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
    }
    $categories = is_array($categories) ? $categories : [];
@endphp

<div class="space-y-4" 
     x-data="{ 
         categories: @js($categories),
         loading: false,
        async refresh() {
            this.loading = true;
            try {
                // Перевіряємо, чи метод існує
                if (typeof $wire.getSchemeCategories === 'function') {
                    const data = await $wire.call('getSchemeCategories');
                    if (data && Array.isArray(data)) {
                        this.categories = data;
                    } else {
                        this.categories = [];
                    }
                } else {
                    // Якщо метод не існує (створення нового готелю), використовуємо PHP дані
                    this.categories = @js($categories) || [];
                }
            } catch (error) {
                console.error('Error loading categories:', error);
                // У випадку помилки використовуємо PHP дані
                this.categories = @js($categories) || [];
            } finally {
                this.loading = false;
            }
        },
         init() {
             // Завантажуємо категорії при ініціалізації
             this.refresh();
         }
     }"
     @scheme-generated.window="refresh()"
     @scheme-deleted.window="refresh()">
    <div class="flex gap-3 mb-6">
        <button
            type="button"
            x-bind:disabled="loading"
            x-on:click="
                // Спочатку перевіряємо наявність даних на сервері
                $wire.call('checkSchemeData')
                    .then((result) => {
                        // Перевіряємо, чи є будь-які збережені дані
                        const hasData = result && ((result.has_data && result.items_count > 0) || (result.categories_count > 0));
                        let shouldProceed = true;
                        
                        if (hasData) {
                            const itemsText = result.items_count > 0 ? ' (' + result.items_count + ' записів)' : '';
                            const message = 'Ви дійсно хочете оновити схему? Ця дія зітре всі дані' + itemsText + '.';
                            shouldProceed = confirm(message);
                        }
                        
                        if (!shouldProceed) {
                            return Promise.reject('Cancelled by user');
                        }
                        
                        // Якщо підтверджено або немає даних, викликаємо генерацію
                        loading = true;
                        return $wire.call('generateScheme', true);
                    })
                    .then(() => {
                        $dispatch('scheme-generated');
                        setTimeout(() => refresh(), 500);
                    })
                    .catch((error) => {
                        if (error !== 'Cancelled by user') {
                            console.error('Error:', error);
                        }
                    })
                    .finally(() => {
                        loading = false;
                    });
            "
            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 disabled:bg-gray-400 disabled:cursor-not-allowed rounded transition-colors"
            style="background-color: #111827 !important; color: white !important;"
        >
            Згенерувати схему
        </button>
        
        <button
            type="button"
            x-bind:disabled="loading"
            x-on:click="
                // Спочатку перевіряємо наявність даних на сервері
                $wire.call('checkSchemeData')
                    .then((result) => {
                        // Перевіряємо, чи є будь-які збережені дані
                        const hasData = result && ((result.has_data && result.items_count > 0) || (result.categories_count > 0));
                        let shouldProceed = true;
                        
                        if (hasData) {
                            const itemsText = result.items_count > 0 ? ' (' + result.items_count + ' записів)' : '';
                            const message = 'Ви дійсно хочете оновити схему? Ця дія зітре всі дані' + itemsText + '.';
                            shouldProceed = confirm(message);
                        }
                        
                        if (!shouldProceed) {
                            return Promise.reject('Cancelled by user');
                        }
                        
                        // Якщо підтверджено або немає даних, викликаємо генерацію
                        loading = true;
                        return $wire.call('generateScheme', true);
                    })
                    .then(() => {
                        $dispatch('scheme-generated');
                        setTimeout(() => refresh(), 500);
                    })
                    .catch((error) => {
                        if (error !== 'Cancelled by user') {
                            console.error('Error:', error);
                        }
                    })
                    .finally(() => {
                        loading = false;
                    });
            "
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded transition-colors"
            style="background-color: #2563eb !important; color: white !important;"
        >
            Оновити схему
        </button>
        
        <button
            type="button"
            x-bind:disabled="loading"
            x-on:click="
                // Перевіряємо наявність даних
                $wire.call('checkSchemeData')
                    .then((result) => {
                        const hasData = result && ((result.has_data && result.items_count > 0) || (result.categories_count > 0));
                        let shouldProceed = true;
                        
                        if (hasData) {
                            const itemsText = result.items_count > 0 ? ' (' + result.items_count + ' записів)' : '';
                            const message = 'Ви дійсно хочете видалити схему? Ця дія видалить всі дані' + itemsText + '.';
                            shouldProceed = confirm(message);
                        } else {
                            const message = 'Схема порожня. Ви дійсно хочете видалити її?';
                            shouldProceed = confirm(message);
                        }
                        
                        if (!shouldProceed) {
                            return Promise.reject('Cancelled by user');
                        }
                        
                        loading = true;
                        return $wire.call('deleteScheme');
                    })
                    .then(() => {
                        $dispatch('scheme-deleted');
                        setTimeout(() => refresh(), 500);
                    })
                    .catch((error) => {
                        if (error !== 'Cancelled by user') {
                            console.error('Error:', error);
                        }
                    })
                    .finally(() => {
                        loading = false;
                    });
            "
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded transition-colors"
            style="background-color: #dc2626 !important; color: white !important;"
        >
            Видалити схему
        </button>
    </div>

    <template x-if="loading">
        <div class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-gray-300 border-t-gray-900"></div>
            <p class="mt-3 text-sm text-gray-500">Завантаження...</p>
        </div>
    </template>
    
    <template x-if="!loading && categories.length > 0">
        <div class="space-y-6" wire:ignore>
            <template x-for="category in categories" :key="category.id">
                <div class="bg-white border border-gray-200 rounded">
                    <!-- Заголовок категорії -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900" x-text="category.name"></h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <span x-text="category.rooms_count"></span> номерів · 
                                    <span x-text="category.total_places"></span> місць
                                </p>
                            </div>
                            <button
                                type="button"
                                x-on:click="
                                    const roomsToSave = category.rooms.map(room => ({
                                        id: room.id,
                                        room_number: room.room_number || '',
                                        places: room.places.map(place => {
                                            const price = parseFloat(place.price) || 0;
                                            const advance = parseFloat(place.advance) || 0;
                                            const balance = price - advance;
                                            return {
                                                id: place.id,
                                                place_number: place.place_number,
                                                meals: place.meals || 'no_meals',
                                                price: price,
                                                first_name: place.first_name || '',
                                                last_name: place.last_name || '',
                                                phone: place.phone || '',
                                                telegram: place.telegram || '',
                                                advance: advance,
                                                balance: balance,
                                                has_transfer_there: place.has_transfer_there || false,
                                                has_transfer_back: place.has_transfer_back || false,
                                                info: place.info || ''
                                            };
                                        })
                                    }));
                                    
                                    $wire.call('saveSchemeItems', category.id, roomsToSave)
                                        .then(() => {
                                            loading = false;
                                        })
                                        .catch((error) => {
                                            console.error('Error saving:', error);
                                            loading = false;
                                        });
                                "
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors"
                                style="background-color: #2563eb !important; color: white !important;"
                            >
                                Зберегти
                            </button>
                        </div>
                    </div>
                    
                    <!-- Таблиця -->
                    <div class="overflow-x-auto" wire:ignore>
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-300 bg-gray-50">
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Номер кімнати</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Прізвище</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Ім'я</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Телефон</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Телеграм</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Вартість</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Аванс</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Залишок</th>
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700">
                                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                    </th>
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700">
                                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                        </svg>
                                    </th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700">Інформація</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(room, roomIndex) in category.rooms" :key="'room-' + roomIndex + '-' + (room.id || 'new')">
                                    <template x-for="(place, placeIndex) in room.places" :key="'place-' + placeIndex + '-' + (place.id || 'new')">
                                        <tr class="hover:bg-gray-50" 
                                            :class="placeIndex === room.places.length - 1 ? 'border-b-2 border-gray-400' : ''">
                                            <!-- Номер кімнати з rowspan -->
                                            <template x-if="placeIndex === 0">
                                                <td class="px-2 py-1.5 align-middle text-center border-r border-gray-300 bg-gray-50" 
                                                    x-bind:rowspan="room.places.length">
                                                    <input 
                                                        type="text"
                                                        x-model="room.room_number"
                                                        placeholder="—"
                                                        x-on:keydown.enter.prevent="$event.target.blur()"
                                                        class="w-full px-1.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                    />
                                                </td>
                                            </template>
                                            <!-- Прізвище -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="text"
                                                    x-model="place.last_name"
                                                    placeholder="—"
                                                    readonly
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                            <!-- Ім'я -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="text"
                                                    x-model="place.first_name"
                                                    placeholder="—"
                                                    readonly
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                            <!-- Телефон -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="text"
                                                    x-model="place.phone"
                                                    placeholder="—"
                                                    readonly
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                            <!-- Телеграм -->
                                            <td class="px-2 py-1.5">
                                                <div class="flex items-center">
                                                    <span class="text-xs text-gray-400 mr-1">@</span>
                                                    <input 
                                                        type="text"
                                                        x-model="place.telegram"
                                                        placeholder="nickname"
                                                        readonly
                                                        class="flex-1 px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                    />
                                                </div>
                                            </td>
                                            <!-- Вартість -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="number"
                                                    x-model="place.price"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    readonly
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                            <!-- Аванс -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="number"
                                                    x-model="place.advance"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    readonly
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                            <!-- Залишок -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="number"
                                                    x-model="place.balance"
                                                    step="0.01"
                                                    readonly
                                                    placeholder="0.00"
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                            <!-- Трансфер туди -->
                                            <td class="px-2 py-1.5 text-center">
                                                <button
                                                    type="button"
                                                    x-on:click="place.has_transfer_there = !place.has_transfer_there"
                                                    class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                                    :class="place.has_transfer_there ? '!bg-green-600' : 'bg-gray-300'"
                                                    :style="place.has_transfer_there ? 'background-color: #16a34a !important;' : ''"
                                                >
                                                    <span
                                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                                        :class="place.has_transfer_there ? 'translate-x-5' : 'translate-x-1'"
                                                    ></span>
                                                </button>
                                            </td>
                                            <!-- Трансфер назад -->
                                            <td class="px-2 py-1.5 text-center">
                                                <button
                                                    type="button"
                                                    x-on:click="place.has_transfer_back = !place.has_transfer_back"
                                                    class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                                    :class="place.has_transfer_back ? '!bg-green-600' : 'bg-gray-300'"
                                                    :style="place.has_transfer_back ? 'background-color: #16a34a !important;' : ''"
                                                >
                                                    <span
                                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                                        :class="place.has_transfer_back ? 'translate-x-5' : 'translate-x-1'"
                                                    ></span>
                                                </button>
                                            </td>
                                            <!-- Інформація -->
                                            <td class="px-2 py-1.5">
                                                <input 
                                                    type="text"
                                                    x-model="place.info"
                                                    placeholder="—"
                                                    readonly
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
                                                />
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </template>
    
    <template x-if="!loading && categories.length === 0">
        <div class="text-center py-12">
            <p class="text-sm text-gray-500">Схема ще не згенерована. Натисніть кнопку "Згенерувати схему" для створення категорій.</p>
        </div>
    </template>
</div>


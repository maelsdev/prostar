@php
    use App\Models\Tour;
    use App\Models\CrmTable;
    use App\Models\CrmCategory;
    
    // Отримуємо категорії через $record або через Livewire
    $categories = [];
    $tourId = null;
    
    // Спробуємо отримати ID туру
    if (isset($record)) {
        if (is_object($record) && isset($record->id)) {
            $tourId = $record->id;
        } elseif (is_numeric($record)) {
            $tourId = $record;
        }
    }
    
    // Якщо не отримали ID, спробуємо через Livewire
    if (!$tourId) {
        try {
            $livewireComponent = \Livewire\Livewire::current();
            if ($livewireComponent && property_exists($livewireComponent, 'record')) {
                $recordObj = $livewireComponent->record;
                if ($recordObj && is_object($recordObj) && isset($recordObj->id)) {
                    $tourId = $recordObj->id;
                }
            }
        } catch (\Exception $e) {
            // Ігноруємо помилку
        }
    }
    
    // Отримуємо дані про трансфери з туру
    $transferCosts = [
        'there' => 0, // Вартість трансферу туди
        'back' => 0, // Вартість трансферу назад
    ];
    
    if ($tourId) {
        $tour = Tour::find($tourId);
        if ($tour && $tour->calculator_transfers) {
            $transfers = is_array($tour->calculator_transfers) ? $tour->calculator_transfers : json_decode($tour->calculator_transfers, true);
            
            if (is_array($transfers)) {
                foreach ($transfers as $transfer) {
                    if (!is_array($transfer)) continue;
                    
                    $transferType = $transfer['transfer_type'] ?? null;
                    
                    // Для потяга
                    if ($transferType === 'train') {
                        $trainToPrice = (float)($transfer['train_to_price'] ?? 0);
                        $trainToBooking = (float)($transfer['train_to_booking'] ?? 0);
                        $trainFromPrice = (float)($transfer['train_from_price'] ?? 0);
                        $trainFromBooking = (float)($transfer['train_from_booking'] ?? 0);
                        
                        $transferCosts['there'] += $trainToPrice + $trainToBooking;
                        $transferCosts['back'] += $trainFromPrice + $trainFromBooking;
                    }
                    // Для ГАЗ 66
                    elseif ($transferType === 'gaz66') {
                        $gaz66ToPrice = (float)($transfer['gaz66_to_price'] ?? 0);
                        $gaz66ToSeats = (float)($transfer['gaz66_to_seats'] ?? 1);
                        $gaz66FromPrice = (float)($transfer['gaz66_from_price'] ?? 0);
                        $gaz66FromSeats = (float)($transfer['gaz66_from_seats'] ?? 1);
                        
                        if ($gaz66ToSeats > 0) {
                            $transferCosts['there'] += $gaz66ToPrice / $gaz66ToSeats;
                        }
                        if ($gaz66FromSeats > 0) {
                            $transferCosts['back'] += $gaz66FromPrice / $gaz66FromSeats;
                        }
                    }
                }
            }
        }
    }
    
    if ($tourId) {
        $crmTable = CrmTable::with(['categories.room', 'categories.items.places'])->where('tour_id', $tourId)->first();
        if ($crmTable) {
            $categories = $crmTable->categories->map(function ($category) {
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
         transferCosts: @js($transferCosts),
         // Функція для оновлення originalPrice на основі поточної ціни
         updateOriginalPrice(place) {
             let currentPrice = parseFloat(place.price) || 0;
             // originalPrice = поточна ціна + вартість вимкнених трансферів
             let transferCost = 0;
             if (!place.has_transfer_there) transferCost += this.transferCosts.there;
             if (!place.has_transfer_back) transferCost += this.transferCosts.back;
             place.originalPrice = Math.round(currentPrice + transferCost);
         },
         // Функція для перерахунку ціни при зміні перемикачів трансферів
         recalculatePrice(place) {
             // Якщо originalPrice не встановлено, встановлюємо його на основі поточної ціни
             if (!place.originalPrice && place.originalPrice !== 0) {
                 this.updateOriginalPrice(place);
             }
             
             let newPrice = place.originalPrice;
             
             // Якщо трансфер туди вимкнено, віднімаємо вартість трансферу туди
             if (!place.has_transfer_there) {
                 newPrice -= this.transferCosts.there;
             }
             
             // Якщо трансфер назад вимкнено, віднімаємо вартість трансферу назад
             if (!place.has_transfer_back) {
                 newPrice -= this.transferCosts.back;
             }
             
             // Оновлюємо ціну та баланс з округленням
             place.price = Math.max(0, Math.round(newPrice));
             place.balance = Math.round(parseFloat(place.price || 0) - parseFloat(place.advance || 0));
         },
        async refresh() {
            this.loading = true;
            try {
                // Перезавантажуємо сторінку для оновлення даних
                window.location.reload();
            } catch (error) {
                console.error('Error loading categories:', error);
                this.categories = @js($categories) || [];
            } finally {
                this.loading = false;
            }
        },
         init() {
             // Завантажуємо категорії при ініціалізації
             this.categories = @js($categories) || [];
             
             // Встановлюємо початкові ціни для всіх місць
             // originalPrice - це ціна з усіма трансферами включеними
             this.categories.forEach(category => {
                 category.rooms.forEach(room => {
                     room.places.forEach(place => {
                         let currentPrice = parseFloat(place.price) || 0;
                         
                         // Обчислюємо originalPrice (ціна з усіма трансферами)
                         // Якщо трансфер вимкнено, додаємо його вартість до поточної ціни
                         let originalPrice = currentPrice;
                         if (!place.has_transfer_there) {
                             originalPrice += this.transferCosts.there;
                         }
                         if (!place.has_transfer_back) {
                             originalPrice += this.transferCosts.back;
                         }
                         
                         place.originalPrice = Math.round(originalPrice);
                     });
                 });
             });
         }
     }">
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
                        <div>
                            <h3 class="text-base font-semibold text-gray-900" x-text="category.name"></h3>
                            <p class="mt-1 text-sm text-gray-500">
                                <span x-text="category.rooms_count"></span> номерів · 
                            <span x-text="category.total_places"></span> місць
                            </p>
                        </div>
                    </div>
                    
                    <!-- Таблиця -->
                    <div class="overflow-x-auto" wire:ignore>
                        <table class="w-full text-xs">
                                <thead>
                                <tr class="border-b border-gray-300 bg-gray-50">
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 w-16">Номер кімнати</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 w-56">Прізвище</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 w-[168px]">Ім'я</th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 w-36">Телефон</th>
                                    <th class="px-0.5 py-1.5 text-left text-xs font-semibold text-gray-700 w-6">Телеграм</th>
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 w-20">Вартість</th>
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 w-20">Аванс</th>
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 w-20">Залишок</th>
                                    <th class="px-1 py-1.5 text-center text-xs font-semibold text-gray-700 w-12">
                                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                    </th>
                                    <th class="px-1 py-1.5 text-center text-xs font-semibold text-gray-700 w-12">
                                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                        </svg>
                                    </th>
                                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 w-28">Інформація</th>
                                    <th class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 w-24">Зберегти</th>
                                    </tr>
                                </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(room, roomIndex) in category.rooms" :key="'room-' + roomIndex + '-' + (room.id || 'new')">
                                    <template x-for="(place, placeIndex) in room.places" :key="'place-' + placeIndex + '-' + (place.id || 'new')">
                                        <tr class="hover:bg-gray-50" 
                                            :class="placeIndex === room.places.length - 1 ? 'border-b-2 border-gray-400' : ''">
                                            <!-- Номер кімнати з rowspan -->
                                            <template x-if="placeIndex === 0">
                                                <td class="px-2 py-1.5 align-middle text-center border-r border-gray-300 bg-gray-50 font-medium w-16" 
                                                    x-bind:rowspan="room.places.length">
                                                    <span x-text="room.room_number || '—'" class="text-xs text-gray-700"></span>
                                                </td>
                                            </template>
                                            <!-- Прізвище -->
                                            <td class="px-2 py-1.5 w-56">
                                                <input 
                                                    type="text"
                                                    x-model="place.last_name"
                                                    placeholder="—"
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                />
                                            </td>
                                            <!-- Ім'я -->
                                            <td class="px-2 py-1.5 w-[168px]">
                                                <input 
                                                    type="text"
                                                    x-model="place.first_name"
                                                    placeholder="—"
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                />
                                            </td>
                                            <!-- Телефон -->
                                            <td class="px-2 py-1.5 w-36">
                                                <input 
                                                    type="text"
                                                    x-model="place.phone"
                                                    placeholder="—"
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                />
                                            </td>
                                            <!-- Телеграм -->
                                            <td class="px-0.5 py-1.5 w-6">
                                                <div class="flex items-center">
                                                    <span class="text-xs text-gray-400 mr-0.5">@</span>
                                                    <input 
                                                        type="text"
                                                        x-model="place.telegram"
                                                        placeholder=""
                                                        class="flex-1 px-0.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                    />
                                                </div>
                                            </td>
                                            <!-- Вартість -->
                                            <td class="px-2 py-1.5 text-center w-20">
                                                <input 
                                                    type="number"
                                                    x-model="place.price"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0"
                                                    x-on:input="
                                                        let newPrice = Math.round(parseFloat(place.price || 0));
                                                        place.price = newPrice;
                                                        // Оновлюємо originalPrice на основі нової ціни
                                                        updateOriginalPrice(place);
                                                        // Оновлюємо баланс
                                                        place.balance = Math.round(newPrice - parseFloat(place.advance || 0));
                                                    "
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-center"
                                                />
                                            </td>
                                            <!-- Аванс -->
                                            <td class="px-2 py-1.5 text-center w-20">
                                                <div class="flex items-center gap-1 justify-center">
                                                    <input 
                                                        type="number"
                                                        x-model="place.advance"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0"
                                                        x-on:input="place.balance = Math.round(parseFloat(place.price || 0) - parseFloat(place.advance || 0))"
                                                        :class="parseFloat(place.advance || 0) > 0 ? 'bg-green-50 border-green-300' : 'bg-white border-gray-300'"
                                                        class="w-14 px-1 py-0.5 text-xs border focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center"
                                                    />
                                                    <button
                                                        type="button"
                                                        x-on:click="
                                                            if (!place.id) {
                                                                alert('Спочатку збережіть місце');
                                                                return;
                                                            }
                                                            const advanceValue = Math.round(parseFloat(place.advance || 0) * 100) / 100;
                                                            if (advanceValue <= 0) {
                                                                alert('Введіть суму авансу');
                                                                return;
                                                            }
                                                            loading = true;
                                                            $wire.call('confirmAdvancePayment', place.id, advanceValue)
                                                                .then(() => {
                                                                    loading = false;
                                                                    place.balance = Math.round(parseFloat(place.price || 0) - advanceValue);
                                                                })
                                                                .catch((error) => {
                                                                    console.error('Error confirming payment:', error);
                                                                    loading = false;
                                                                });
                                                        "
                                                        class="px-1.5 py-0.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded transition-colors flex-shrink-0 min-w-[20px] h-[20px] flex items-center justify-center"
                                                        title="Підтвердити оплату"
                                                        style="background-color: #16a34a !important;"
                                                    >
                                                        ✓
                                                    </button>
                                                </div>
                                            </td>
                                            <!-- Залишок -->
                                            <td class="px-2 py-1.5 text-center w-20">
                                                <input 
                                                    type="number"
                                                    x-model="place.balance"
                                                    step="0.01"
                                                    readonly
                                                    placeholder="0"
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed text-center"
                                                />
                                            </td>
                                            <!-- Трансфер туди -->
                                            <td class="px-1 py-1.5 text-center w-12">
                                                <button
                                                    type="button"
                                                    x-on:click="
                                                        place.has_transfer_there = !place.has_transfer_there; 
                                                        recalculatePrice(place);
                                                        if (place.id) {
                                                            $wire.call('savePlaceField', place.id, 'has_transfer_there', place.has_transfer_there);
                                                        }
                                                    "
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
                                            <td class="px-1 py-1.5 text-center w-12">
                                                <button
                                                    type="button"
                                                    x-on:click="
                                                        place.has_transfer_back = !place.has_transfer_back; 
                                                        recalculatePrice(place);
                                                        if (place.id) {
                                                            $wire.call('savePlaceField', place.id, 'has_transfer_back', place.has_transfer_back);
                                                        }
                                                    "
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
                                            <td class="px-2 py-1.5 w-28">
                                                <input 
                                                    type="text"
                                                    x-model="place.info"
                                                    placeholder="—"
                                                    x-on:keydown.enter.prevent="$event.target.blur()"
                                                    class="w-full px-1.5 py-0.5 text-xs border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                />
                                            </td>
                                            <!-- Кнопка збереження -->
                                            <td class="px-2 py-1.5 text-center w-24">
                                                <button
                                                    type="button"
                                                    x-on:click="
                                                        if (!place.id) {
                                                            alert('Спочатку збережіть місце');
                                                            return;
                                                        }
                                                        const priceValue = Math.round(parseFloat(place.price || 0));
                                                        $wire.call('savePlaceData', place.id, {
                                                            first_name: place.first_name || '',
                                                            last_name: place.last_name || '',
                                                            phone: place.phone || '',
                                                            telegram: place.telegram || '',
                                                            info: place.info || '',
                                                            price: priceValue,
                                                            has_transfer_there: place.has_transfer_there || false,
                                                            has_transfer_back: place.has_transfer_back || false
                                                        });
                                                        // Оновлюємо баланс після збереження
                                                        place.balance = Math.round(priceValue - parseFloat(place.advance || 0));
                                                    "
                                                    class="px-2 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors whitespace-nowrap"
                                                    title="Зберегти всі дані"
                                                    style="background-color: #2563eb !important;"
                                                >
                                                    Зберегти
                                                </button>
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
            <p class="text-sm text-gray-500">CRM таблиця порожня.</p>
        </div>
    </template>
</div>

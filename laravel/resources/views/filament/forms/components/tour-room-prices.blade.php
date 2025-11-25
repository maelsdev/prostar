@php
    $hotelId = $hotel_id ?? null;
    $roomPrices = $room_prices ?? [];
    $transferTo = (float)($transfer_price_to_tour ?? 0);
    $transferFrom = (float)($transfer_price_from_tour ?? 0);
    $roomsData = [];
    
    if ($hotelId) {
        $hotel = \App\Models\Hotel::with('rooms')->find($hotelId);
        if ($hotel && $hotel->rooms) {
            foreach ($hotel->rooms as $room) {
                $roomId = $room->id;
                $price = 0;
                $roomMargin = 0;
                
                // Отримуємо збережені дані з room_prices, якщо вони є
                if (!empty($roomPrices) && is_array($roomPrices) && isset($roomPrices[$roomId])) {
                    $priceData = $roomPrices[$roomId];
                    $price = is_array($priceData) ? ($priceData['price'] ?? 0) : 0;
                    $roomMargin = is_array($priceData) ? ($priceData['margin'] ?? 0) : 0;
                }
                
                // Розраховуємо загальну вартість
                $totalPrice = $price + $roomMargin + $transferTo + $transferFrom;
                
                $roomsData[$roomId] = [
                    'room_id' => $room->id,
                    'room_type' => $room->room_type,
                    'bed_types' => $room->bed_types,
                    'meals' => $room->meals,
                    'places_per_room' => $room->places_per_room,
                    'price' => $price,
                    'margin' => $roomMargin,
                    'total_price' => $totalPrice,
                ];
            }
        }
    }
@endphp

@if(!empty($roomsData))
<div class="space-y-4" 
     x-data="roomPricesData()"
     x-init="
         // Слухаємо зміни трансферів через Livewire
         Livewire.on('updated', () => {
             const updateValues = () => {
                 const transferToInput = document.querySelector('input[wire\\:model*=\"transfer_price_to_tour\"], input[name*=\"transfer_price_to_tour\"]');
                 const transferFromInput = document.querySelector('input[wire\\:model*=\"transfer_price_from_tour\"], input[name*=\"transfer_price_from_tour\"]');
                 
                 if (transferToInput) {
                     transferTo = parseFloat(transferToInput.value) || 0;
                 }
                 if (transferFromInput) {
                     transferFrom = parseFloat(transferFromInput.value) || 0;
                 }
             };
             setTimeout(updateValues, 100);
         });
     ">
    <script>
        function roomPricesData() {
            return {
                roomPrices: {!! json_encode($roomsData, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) !!},
                transferTo: {{ $transferTo }},
                transferFrom: {{ $transferFrom }},
                updatePrice(roomId, price) {
                    if (!this.roomPrices[roomId]) {
                        return;
                    }
                    this.roomPrices[roomId].price = parseFloat(price) || 0;
                    // Оновлюємо загальну вартість
                    this.roomPrices[roomId].total_price = this.getTotalPrice(roomId);
                    this.saveRoomPrices();
                },
                updateMargin(roomId, margin) {
                    if (!this.roomPrices[roomId]) {
                        return;
                    }
                    this.roomPrices[roomId].margin = parseFloat(margin) || 0;
                    // Оновлюємо загальну вартість
                    this.roomPrices[roomId].total_price = this.getTotalPrice(roomId);
                    this.saveRoomPrices();
                },
                getTotalPrice(roomId) {
                    if (!this.roomPrices[roomId]) return 0;
                    // Оновлюємо значення трансферів перед розрахунком
                    this.updateTransferValues();
                    const roomPrice = parseFloat(this.roomPrices[roomId].price) || 0;
                    const roomMargin = parseFloat(this.roomPrices[roomId].margin) || 0;
                    const transferTo = parseFloat(this.transferTo) || 0;
                    const transferFrom = parseFloat(this.transferFrom) || 0;
                    return roomPrice + roomMargin + transferTo + transferFrom;
                },
                updateTransferValues() {
                    const transferToInput = document.querySelector('input[wire\\:model*="transfer_price_to_tour"], input[name*="transfer_price_to_tour"]');
                    const transferFromInput = document.querySelector('input[wire\\:model*="transfer_price_from_tour"], input[name*="transfer_price_from_tour"]');
                    
                    let changed = false;
                    if (transferToInput) {
                        const newValue = parseFloat(transferToInput.value) || 0;
                        if (this.transferTo !== newValue) {
                            this.transferTo = newValue;
                            changed = true;
                        }
                    }
                    if (transferFromInput) {
                        const newValue = parseFloat(transferFromInput.value) || 0;
                        if (this.transferFrom !== newValue) {
                            this.transferFrom = newValue;
                            changed = true;
                        }
                    }
                    
                    // Якщо значення трансферів змінилися, зберігаємо room_prices
                    if (changed) {
                        this.saveRoomPrices();
                    }
                },
                saveRoomPrices() {
                    const dataToSave = {};
                    Object.keys(this.roomPrices).forEach(id => {
                        const room = this.roomPrices[id];
                        // Оновлюємо загальну вартість перед збереженням
                        const totalPrice = this.getTotalPrice(id);
                        room.total_price = totalPrice;
                        
                        dataToSave[id] = {
                            room_id: room.room_id,
                            room_type: room.room_type,
                            bed_types: room.bed_types,
                            meals: room.meals,
                            places_per_room: room.places_per_room,
                            price: room.price,
                            margin: room.margin || 0,
                            total_price: totalPrice
                        };
                    });
                    
                    // Зберігаємо дані через Livewire
                    $wire.set('data.room_prices', dataToSave, false);
                    
                    // Також викликаємо збереження форми після невеликої затримки
                    setTimeout(() => {
                        // Перевіряємо, чи є кнопка збереження
                        const saveButton = document.querySelector('button[type="submit"], button[wire\\:click*="save"]');
                        if (saveButton) {
                            // Не викликаємо автоматичне збереження, просто оновлюємо дані
                        }
                    }, 100);
                },
                init() {
                    // Оновлюємо значення трансферів при ініціалізації
                    this.updateTransferValues();
                    
                    // Слухаємо зміни в полях трансферів
                    const handleInput = (e) => {
                        if (e.target && (
                            e.target.name?.includes('transfer_price_to_tour') ||
                            e.target.name?.includes('transfer_price_from_tour') ||
                            e.target.id?.includes('transfer_price_to_tour') ||
                            e.target.id?.includes('transfer_price_from_tour')
                        )) {
                            this.updateTransferValues();
                        }
                    };
                    
                    document.addEventListener('input', handleInput);
                    document.addEventListener('change', handleInput);
                    
                    // Також перевіряємо через інтервал для надійності
                    setInterval(() => {
                        this.updateTransferValues();
                    }, 500);
                },
                getMealsLabel(meals) {
                    const labels = {
                        'breakfast': 'Сніданки',
                        'breakfast_dinner': 'Сніданок + вечеря',
                        'no_meals': 'Без харчування'
                    };
                    return labels[meals] || 'Без харчування';
                },
                getBedTypesLabel(bedTypes) {
                    if (!bedTypes || typeof bedTypes !== 'object') return '';
                    const single = bedTypes.single || 0;
                    const double = bedTypes.double || 0;
                    const parts = [];
                    if (single > 0) parts.push(single + ' односпальн' + (single > 1 ? 'их' : 'е'));
                    if (double > 0) parts.push(double + ' двоспальн' + (double > 1 ? 'их' : 'е'));
                    return parts.join(', ') || '—';
                }
            };
        }
    </script>
    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="border-b border-gray-300 bg-gray-50 dark:bg-gray-800">
                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Тип номера</th>
                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Вартість за особу</th>
                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Маржа</th>
                    <th class="px-2 py-1.5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Загальна вартість</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="(roomId) in Object.keys(roomPrices)" :key="roomId">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-2 py-1.5">
                            <div class="space-y-0.5">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="roomPrices[roomId].room_type || 'Тип номера'"></div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    <span x-text="getBedTypesLabel(roomPrices[roomId].bed_types)"></span>
                                    <span class="mx-1">•</span>
                                    <span x-text="getMealsLabel(roomPrices[roomId].meals)"></span>
                                    <span class="mx-1">•</span>
                                    <span x-text="(roomPrices[roomId].places_per_room || 0) + ' місць'"></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-1.5">
                            <div class="relative">
                                <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-xs">₴</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :value="roomPrices[roomId].price || 0"
                                    x-on:input="updatePrice(roomId, $event.target.value)"
                                    class="w-full pl-6 pr-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                />
                            </div>
                        </td>
                        <td class="px-2 py-1.5">
                            <div class="relative">
                                <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-xs">₴</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :value="roomPrices[roomId].margin || 0"
                                    x-on:input="updateMargin(roomId, $event.target.value)"
                                    class="w-full pl-6 pr-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    placeholder="0.00"
                                />
                            </div>
                        </td>
                        <td class="px-2 py-1.5">
                            <div class="relative">
                                <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-xs">₴</span>
                                <div 
                                    x-text="'₴' + getTotalPrice(roomId).toFixed(2)"
                                    class="w-full pl-6 pr-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-semibold flex items-center"
                                ></div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
@else
<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
    <p class="text-sm text-yellow-800 dark:text-yellow-200">Немає даних про типи номерів. Будь ласка, виконайте імпорт.</p>
</div>
@endif


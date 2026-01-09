<div class="mt-4">
    <button
        type="button"
        x-data="{
            createBulkItems() {
                const bulkCount = $wire.get('data.bulk_count') || 1;
                const bulkMeals = $wire.get('data.bulk_meals') || 'no_meals';
                const roomId = $wire.get('data.room_id');
                const priceType = $wire.get('data.price_type');
                
                if (!roomId || bulkCount < 1) {
                    $dispatch('notify', {
                        type: 'danger',
                        title: 'Помилка',
                        body: 'Оберіть номер та вкажіть кількість'
                    });
                    return;
                }
                
                // Отримуємо дані про номер через AJAX
                fetch(`/admin/hotels/room-data/${roomId}`)
                    .then(response => response.json())
                    .then(room => {
                        if (!room) {
                            return;
                        }
                        
                        const itemsToCreate = [];
                        let totalItems = 0;
                        
                        if (priceType === 'per_room') {
                            const roomsCount = room.rooms_count;
                            totalItems = roomsCount * bulkCount;
                            
                            for (let i = 0; i < totalItems; i++) {
                                itemsToCreate.push({
                                    room_number: '',
                                    meals: bulkMeals,
                                    price: 0,
                                    first_name: '',
                                    last_name: ''
                                });
                            }
                        } else {
                            const bedTypes = typeof room.bed_types === 'string' 
                                ? JSON.parse(room.bed_types) 
                                : room.bed_types;
                            const singleBeds = parseInt(bedTypes.single || 0);
                            const doubleBeds = parseInt(bedTypes.double || 0);
                            const placesCount = singleBeds + (doubleBeds * 2);
                            totalItems = placesCount * bulkCount;
                            
                            for (let i = 0; i < totalItems; i++) {
                                itemsToCreate.push({
                                    room_number: '',
                                    meals: bulkMeals,
                                    price: 0
                                });
                            }
                        }
                        
                        // Отримуємо поточні записи
                        const currentItems = $wire.get('data.items') || [];
                        
                        // Додаємо нові записи
                        const newItems = [...currentItems, ...itemsToCreate];
                        
                        // Встановлюємо нові записи в Repeater
                        $wire.set('data.items', newItems);
                        
                        // Очищаємо поле кількості
                        $wire.set('data.bulk_count', 1);
                        
                        $dispatch('notify', {
                            type: 'success',
                            title: 'Успішно',
                            body: `Створено ${totalItems} записів. Заповніть дані в таблиці нижче.`
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        $dispatch('notify', {
                            type: 'danger',
                            title: 'Помилка',
                            body: 'Не вдалося створити записи'
                        });
                    });
            }
        }"
        @click="createBulkItems()"
        class="inline-flex items-center px-4 py-2 bg-success-600 hover:bg-success-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-success-500 focus:ring-offset-2"
    >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Створити записи
    </button>
</div>









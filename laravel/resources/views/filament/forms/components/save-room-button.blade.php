<div class="mt-4 flex justify-end" 
     x-data="{ 
         loading: false,
         roomId: null,
         init() {
             // Знаходимо найближчий repeater item
             const repeaterItem = this.$el.closest('[data-repeater-item]');
             if (!repeaterItem) return;
             
             // Спробуємо знайти hidden input з id
             const idInput = repeaterItem.querySelector('input[name*="[id]"]');
             if (idInput && idInput.value) {
                 this.roomId = parseInt(idInput.value);
                 return;
             }
             
             // Альтернативний спосіб - через data атрибут
             const itemId = repeaterItem.getAttribute('data-item-id');
             if (itemId) {
                 this.roomId = parseInt(itemId);
             }
         }
     }">
    <button
        x-show="roomId"
        type="button"
        x-bind:disabled="loading || !roomId"
        x-on:click="
            if (!roomId) return;
            loading = true;
            $wire.call('saveRoom', roomId)
                .then(() => {
                    loading = false;
                    $wire.$refresh();
                    $dispatch('room-saved');
                })
                .catch(() => {
                    loading = false;
                });
        "
        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <svg x-show="loading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span x-text="loading ? 'Збереження...' : 'Зберегти'"></span>
    </button>
</div>


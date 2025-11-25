<div class="mt-4" 
     x-data="{ 
         loading: false,
         getHotelId() {
             const select = document.querySelector('select[wire\\:model*=\"hotel_id\"], select[name*=\"hotel_id\"], select[id*=\"hotel_id\"]');
             if (select && select.value) {
                 return parseInt(select.value);
             }
             return null;
         }
     }">
    <button
        type="button"
        x-bind:disabled="loading"
        x-on:click="
            const hotelId = getHotelId();
            if (!hotelId) {
                alert('Будь ласка, оберіть готель');
                return;
            }
            if (!confirm('Це імпортує всі типи номерів з вибраного готелю. Продовжити?')) {
                return;
            }
            loading = true;
            $wire.call('importHotelRooms', hotelId)
                .then(() => {
                    loading = false;
                    $wire.$refresh();
                })
                .catch(() => {
                    loading = false;
                });
        "
        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        <svg x-show="loading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span x-text="loading ? 'Імпорт...' : 'Імпорт'"></span>
    </button>
</div>


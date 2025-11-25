<div x-data="{ loading: false }">
    <button
        type="button"
        x-bind:disabled="loading"
        x-on:click="
            if (!confirm('Ви дійсно хочете очистити всі поля калькулятора? Ця дія скине всі значення на дефолт.')) {
                return;
            }
            
            loading = true;
            $wire.call('clearCalculatorForm')
                .then(() => {
                    loading = false;
                    $wire.$refresh();
                })
                .catch((error) => {
                    loading = false;
                    console.error('Error:', error);
                    alert('Помилка при очищенні форми');
                });
        "
        class="inline-flex items-center px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        style="background-color: #dc2626 !important; border: 1px solid #b91c1c !important;"
        x-bind:style="loading ? 'background-color: #9ca3af !important;' : 'background-color: #dc2626 !important; border: 1px solid #b91c1c !important;'"
    >
        <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        <svg x-show="loading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span x-text="loading ? 'Очищення...' : 'Очистити форму'"></span>
    </button>
</div>


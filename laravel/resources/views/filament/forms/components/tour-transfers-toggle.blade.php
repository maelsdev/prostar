@php
    $hasTransferTo = $has_transfer_to_tour ?? false;
    $hasTransferFrom = $has_transfer_from_tour ?? false;
@endphp

<div class="space-y-4" 
     x-data="{ 
         hasTransferTo: @js($hasTransferTo),
         hasTransferFrom: @js($hasTransferFrom),
         toggleTransferTo() {
             this.hasTransferTo = !this.hasTransferTo;
             $wire.set('data.has_transfer_to_tour', this.hasTransferTo, false);
             if (!this.hasTransferTo) {
                 $wire.set('data.transfer_price_to_tour', 0, false);
             }
             // Оновлюємо форму
             setTimeout(() => {
                 $wire.$refresh();
             }, 100);
         },
         toggleTransferFrom() {
             this.hasTransferFrom = !this.hasTransferFrom;
             $wire.set('data.has_transfer_from_tour', this.hasTransferFrom, false);
             if (!this.hasTransferFrom) {
                 $wire.set('data.transfer_price_from_tour', 0, false);
             }
             // Оновлюємо форму
             setTimeout(() => {
                 $wire.$refresh();
             }, 100);
         }
     }"
     wire:ignore>
    <div class="grid grid-cols-2 gap-4">
        <!-- Трансфер в тур -->
        <div class="flex items-center justify-between p-4 border border-gray-300 dark:border-gray-600 rounded-lg">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Трансфер в тур</span>
            </div>
            <button
                type="button"
                x-on:click="toggleTransferTo()"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                :class="hasTransferTo ? 'bg-green-600' : 'bg-gray-300'"
                :style="hasTransferTo ? 'background-color: #16a34a !important;' : ''"
            >
                <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                    :class="hasTransferTo ? 'translate-x-6' : 'translate-x-1'"
                ></span>
            </button>
        </div>
        
        <!-- Трансфер з туру -->
        <div class="flex items-center justify-between p-4 border border-gray-300 dark:border-gray-600 rounded-lg">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Трансфер з туру</span>
            </div>
            <button
                type="button"
                x-on:click="toggleTransferFrom()"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                :class="hasTransferFrom ? 'bg-green-600' : 'bg-gray-300'"
                :style="hasTransferFrom ? 'background-color: #16a34a !important;' : ''"
            >
                <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                    :class="hasTransferFrom ? 'translate-x-6' : 'translate-x-1'"
                ></span>
            </button>
        </div>
    </div>
</div>


@php
    $record = $viewData['record'] ?? null;
    $isImage = $viewData['isImage'] ?? false;
    $isFolder = $viewData['isFolder'] ?? false;
@endphp

@if($record)
    <div class="flex items-center justify-center h-48 bg-gray-100 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
        @if($isFolder)
            <div class="text-center">
                <x-heroicon-o-folder class="w-16 h-16 mx-auto text-warning-500" />
                <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Папка</p>
            </div>
        @elseif(!$isImage)
            <div class="text-center">
                <x-heroicon-o-document class="w-16 h-16 mx-auto text-success-500" />
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    {{ $record->mime_type ?? 'Файл' }}
                </p>
            </div>
        @endif
    </div>
@endif


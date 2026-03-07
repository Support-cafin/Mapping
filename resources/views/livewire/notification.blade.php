{{-- resources/views/livewire/notification.blade.php --}}
@if($show)
    <div x-data="{ show: true }" 
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50">
        <div class="{{ [
            'info' => 'bg-blue-100 border-blue-400 text-blue-800',
            'success' => 'bg-green-100 border-green-400 text-green-800',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-800',
            'error' => 'bg-red-100 border-red-400 text-red-800',
        ][$type] }} border px-6 py-4 rounded-lg shadow-lg max-w-sm">
            <div class="flex justify-between items-center">
                <p>{{ $message }}</p>
                <button @click="show = false" class="ml-4 text-lg">&times;</button>
            </div>
        </div>
    </div>
@endif
@if(session()->has('success'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 5000)"
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 z-50">
        <i class="fas fa-check-circle"></i>
        <span style="font-size: 12px;">{{ session('success') }}</span>
    </div>
@endif

@if(session()->has('error'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 8000)"
         class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 z-50">
        <i class="fas fa-exclamation-circle"></i>
        <span style="font-size: 12px;">{{ session('error') }}</span>
    </div>
@endif
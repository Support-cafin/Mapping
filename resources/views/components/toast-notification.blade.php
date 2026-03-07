{{-- TOAST GLOBAL --}}
<div
    x-data="{
        show: false,
        message: '',
        type: 'success',
        timeout: null
    }"
    x-on:toast.window="
        clearTimeout(timeout);
        message = $event.detail.message;
        type = $event.detail.type ?? 'success';
        show = true;
        timeout = setTimeout(() => show = false, 3000);
    "
    x-show="show"
    x-transition
    class="fixed bottom-6 right-6 z-50"
>
    <div
        class="px-5 py-3 rounded-lg shadow-lg text-sm font-medium"
        :class="{
            'bg-green-600 text-white': type === 'success',
            'bg-red-600 text-white': type === 'error',
            'bg-blue-600 text-white': type === 'info'
        }"
    >
        <span x-text="message"></span>
    </div>
</div>

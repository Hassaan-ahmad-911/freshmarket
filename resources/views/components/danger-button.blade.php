<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-red-600 text-white shadow-sm hover:bg-red-700 focus-visible:ring-red-500 active:scale-[0.98]']) }}>
    {{ $slot }}
</button>

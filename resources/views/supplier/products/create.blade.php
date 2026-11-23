<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">New product</h2>
    </x-slot>

    <div class="pb-12 pt-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-0">
            <form method="POST" action="{{ route('supplier.products.store') }}" enctype="multipart/form-data" class="card p-6 space-y-6">
                @csrf
                @include('supplier.products._form')
                <div class="flex justify-end gap-3">
                    <a href="{{ route('supplier.products.index') }}" class="px-4 py-2 text-sm text-stone-600 hover:text-stone-900">Cancel</a>
                    <x-primary-button>Create product</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

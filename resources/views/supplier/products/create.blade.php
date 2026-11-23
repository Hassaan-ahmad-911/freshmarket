<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New product</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('supplier.products.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @include('supplier.products._form')
                <div class="flex justify-end gap-3">
                    <a href="{{ route('supplier.products.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>Create product</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

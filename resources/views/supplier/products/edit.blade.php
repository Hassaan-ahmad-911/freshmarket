<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit: {{ $product->name }}</h2>
            <a href="{{ route('supplier.products.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; All products</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($product->images->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold">Images</h3>
                    <div class="mt-3 grid grid-cols-3 sm:grid-cols-6 gap-3">
                        @foreach ($product->images as $image)
                            <div class="relative group">
                                <img src="{{ $image->url() }}" alt="" class="aspect-square w-full rounded-md object-cover border border-gray-200">
                                @if ($loop->first)
                                    <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 text-[10px] text-white">Main</span>
                                @endif
                                <form method="POST" action="{{ route('supplier.products.images.destroy', [$product, $image]) }}" class="absolute top-1 right-1">
                                    @csrf @method('DELETE')
                                    <button class="rounded-full bg-white/90 px-2 text-red-600 shadow hover:bg-white" aria-label="Remove image">&times;</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('supplier.products.update', $product) }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @method('PUT')
                @include('supplier.products._form')
                <div class="flex justify-end">
                    <x-primary-button>Save changes</x-primary-button>
                </div>
            </form>

            <form method="POST" action="{{ route('supplier.products.destroy', $product) }}"
                  x-data @submit.prevent="if (confirm('Delete this product? Customers will no longer see it.')) $el.submit()"
                  class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                @csrf @method('DELETE')
                <p class="text-sm text-gray-600">Delete this product. Past orders keep their history.</p>
                <x-danger-button>Delete</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>

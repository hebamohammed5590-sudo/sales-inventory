<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Edit Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form
                        method="POST"
                        action="{{ route('products.update', $product) }}"
                        enctype="multipart/form-data"
                    >
                        @csrf

                        @method('PUT')

                        @include('products._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
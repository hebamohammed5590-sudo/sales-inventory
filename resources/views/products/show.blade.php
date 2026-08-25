<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Product Details') }}
            </h2>

            <a
                href="{{ route('products.edit', $product) }}"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
            >
                {{ __('Edit Product') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="space-y-6 p-6">
                    @if ($product->image_path)
                        <img
                            src="{{ asset('storage/'.$product->image_path) }}"
                            alt="{{ $product->name }}"
                            class="h-48 w-48 rounded-md object-cover"
                        >
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Name') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('SKU') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->sku }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Category') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->category->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Status') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{
                                    $product->is_active
                                        ? __('Active')
                                        : __('Inactive')
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Cost Price') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->cost_price }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Selling Price') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->sell_price }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Quantity') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->quantity }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Reorder Level') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $product->reorder_level }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ __('Description') }}
                        </p>

                        <p class="mt-1 text-gray-900">
                            {{ $product->description ?? '-' }}
                        </p>
                    </div>

                    <a
                        href="{{ route('products.index') }}"
                        class="inline-block text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        {{ __('Back to Products') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
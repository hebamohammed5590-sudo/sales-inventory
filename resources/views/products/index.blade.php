<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Products') }}
            </h2>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('products.export', request()->query()) }}"
                    class="rounded-md border border-green-600 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50"
                >
                    {{ __('Export CSV') }}
                </a>

                <a
                    href="{{ route('products.create') }}"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    {{ __('Add Product') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- قسم استيراد المنتجات عبر ملف CSV --}}
            @can('create', \App\Models\Product::class)
                <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{ __('Import Products') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Upload a CSV file to create or update products.
                        </p>
                    </div>

                    <form
                        action="{{ route('products.import') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="flex flex-wrap items-end gap-4"
                    >
                        @csrf

                        <div class="min-w-64 flex-1">
                            <label
                                for="products_csv"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                CSV File
                            </label>

                            <input
                                id="products_csv"
                                name="file"
                                type="file"
                                accept=".csv,.txt"
                                required
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                            >

                            @error('file')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Import CSV
                        </button>

                        <a
                            href="{{ route('products.import.sample') }}"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Download Sample CSV
                        </a>
                    </form>

                    @if ($errors->has('file'))
                        <div class="mt-4 rounded-md bg-red-50 p-4">
                            <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                                @foreach ($errors->get('file') as $error)
                                    <li>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endcan

            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('products.index') }}"
                    class="grid gap-4 md:grid-cols-5"
                >
                    <div>
                        <x-input-label
                            for="search"
                            :value="__('Search')"
                        />

                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            :placeholder="__('Name or SKU')"
                        />
                    </div>

                    <div>
                        <x-input-label
                            for="category_id"
                            :value="__('Category')"
                        />

                        <select
                            id="category_id"
                            name="category_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">
                                {{ __('All Categories') }}
                            </option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected(request('category_id') == $category->id)
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label
                            for="status"
                            :value="__('Status')"
                        />

                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">
                                {{ __('All Statuses') }}
                            </option>

                            <option
                                value="active"
                                @selected(request('status') === 'active')
                            >
                                {{ __('Active') }}
                            </option>

                            <option
                                value="inactive"
                                @selected(request('status') === 'inactive')
                            >
                                {{ __('Inactive') }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <x-input-label
                            for="sort"
                            :value="__('Sort By')"
                        />

                        <select
                            id="sort"
                            name="sort"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option
                                value="created_at"
                                @selected($sort === 'created_at')
                            >
                                {{ __('Date') }}
                            </option>

                            <option
                                value="name"
                                @selected($sort === 'name')
                            >
                                {{ __('Name') }}
                            </option>

                            <option
                                value="sku"
                                @selected($sort === 'sku')
                            >
                                {{ __('SKU') }}
                            </option>

                            <option
                                value="sell_price"
                                @selected($sort === 'sell_price')
                            >
                                {{ __('Selling Price') }}
                            </option>

                            <option
                                value="quantity"
                                @selected($sort === 'quantity')
                            >
                                {{ __('Quantity') }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <x-primary-button>
                            {{ __('Filter') }}
                        </x-primary-button>

                        <a
                            href="{{ route('products.index') }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Image') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Name') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('SKU') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Category') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Cost Price') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Selling Price') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Quantity') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-4 py-3">
                                        @if ($product->image_path)
                                            <img
                                                src="{{ asset('storage/'.$product->image_path) }}"
                                                alt="{{ $product->name }}"
                                                class="h-12 w-12 rounded-md object-cover"
                                            >
                                        @else
                                            <span class="text-sm text-gray-400">
                                                -
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->category->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->cost_price }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->sell_price }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->quantity }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if ($product->is_active)
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-green-700">
                                                {{ __('Active') }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-red-700">
                                                {{ __('Inactive') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-3">
                                            <a
                                                href="{{ route('products.show', $product) }}"
                                                class="text-gray-600 hover:text-gray-900"
                                            >
                                                {{ __('View') }}
                                            </a>

                                            <a
                                                href="{{ route('products.edit', $product) }}"
                                                class="text-indigo-600 hover:text-indigo-800"
                                            >
                                                {{ __('Edit') }}
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('products.destroy', $product) }}"
                                                onsubmit="return confirm('{{ __('Delete this product?') }}')"
                                            >
                                                @csrf

                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="text-red-600 hover:text-red-800"
                                                >
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="9"
                                        class="px-4 py-6 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No products found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
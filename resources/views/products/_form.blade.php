@php
    $editing = isset($product);

    $selectedCategory = old(
        'category_id',
        $editing ? $product->category_id : ''
    );

    $isActive = old(
        'is_active',
        $editing ? $product->is_active : true
    );
@endphp

<div class="space-y-6">
    <div>
        <x-input-label
            for="category_id"
            :value="__('Category')"
        />

        <select
            id="category_id"
            name="category_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            required
        >
            <option value="">
                {{ __('Select a category') }}
            </option>

            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected($selectedCategory == $category->id)
                >
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <x-input-error
            class="mt-2"
            :messages="$errors->get('category_id')"
        />
    </div>

    <div>
        <x-input-label
            for="name"
            :value="__('Product Name')"
        />

        <x-text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $product->name ?? '')"
            required
        />

        <x-input-error
            class="mt-2"
            :messages="$errors->get('name')"
        />
    </div>

    <div>
        <x-input-label
            for="sku"
            :value="__('SKU')"
        />

        <x-text-input
            id="sku"
            name="sku"
            type="text"
            class="mt-1 block w-full"
            :value="old('sku', $product->sku ?? '')"
            :required="$editing"
        />

        @unless ($editing)
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Leave blank to generate automatically.') }}
            </p>
        @endunless

        <x-input-error
            class="mt-2"
            :messages="$errors->get('sku')"
        />
    </div>

    <div>
        <x-input-label
            for="description"
            :value="__('Description')"
        />

        <textarea
            id="description"
            name="description"
            rows="4"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        >{{ old('description', $product->description ?? '') }}</textarea>

        <x-input-error
            class="mt-2"
            :messages="$errors->get('description')"
        />
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label
                for="cost_price"
                :value="__('Cost Price')"
            />

            <x-text-input
                id="cost_price"
                name="cost_price"
                type="text"
                inputmode="decimal"
                class="mt-1 block w-full"
                :value="old('cost_price', $product->cost_price ?? '')"
                required
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('cost_price')"
            />
        </div>

        <div>
            <x-input-label
                for="sell_price"
                :value="__('Selling Price')"
            />

            <x-text-input
                id="sell_price"
                name="sell_price"
                type="text"
                inputmode="decimal"
                class="mt-1 block w-full"
                :value="old('sell_price', $product->sell_price ?? '')"
                required
            />

            <x-input-error
                class="mt-2"
                :messages="$errors->get('sell_price')"
            />
        </div>
    </div>

    <div>
        <x-input-label
            for="reorder_level"
            :value="__('Reorder Level')"
        />

        <x-text-input
            id="reorder_level"
            name="reorder_level"
            type="number"
            min="0"
            class="mt-1 block w-full"
            :value="old('reorder_level', $product->reorder_level ?? 5)"
            required
        />

        <x-input-error
            class="mt-2"
            :messages="$errors->get('reorder_level')"
        />
    </div>

    @if ($editing)
        <div>
            <x-input-label
                :value="__('Current Quantity')"
            />

            <p class="mt-1 text-sm text-gray-700">
                {{ $product->quantity }}
            </p>

            <p class="mt-1 text-sm text-gray-500">
                {{ __('Stock can only be changed through stock movements.') }}
            </p>
        </div>
    @endif

    <div>
        <x-input-label
            for="image"
            :value="__('Product Image')"
        />

        <input
            id="image"
            name="image"
            type="file"
            accept="image/*"
            class="mt-1 block w-full text-sm text-gray-700"
        >

        @if ($editing && $product->image_path)
            <img
                src="{{ asset('storage/'.$product->image_path) }}"
                alt="{{ $product->name }}"
                class="mt-3 h-24 w-24 rounded-md object-cover"
            >
        @endif

        <x-input-error
            class="mt-2"
            :messages="$errors->get('image')"
        />
    </div>

    <div>
        <input
            type="hidden"
            name="is_active"
            value="0"
        >

        <label
            for="is_active"
            class="inline-flex items-center"
        >
            <input
                id="is_active"
                name="is_active"
                type="checkbox"
                value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm"
                @checked((bool) $isActive)
            >

            <span class="ms-2 text-sm text-gray-700">
                {{ __('Active product') }}
            </span>
        </label>

        <x-input-error
            class="mt-2"
            :messages="$errors->get('is_active')"
        />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>
            {{
                $editing
                    ? __('Update Product')
                    : __('Create Product')
            }}
        </x-primary-button>

        <a
            href="{{ route('products.index') }}"
            class="text-sm text-gray-600 hover:text-gray-900"
        >
            {{ __('Cancel') }}
        </a>
    </div>
</div>
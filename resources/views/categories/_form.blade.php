@php
    $editing = isset($category);

    $isActive = old(
        'is_active',
        $editing ? $category->is_active : true
    );
@endphp

<div class="space-y-6">
    <div>
        <x-input-label
            for="name"
            :value="__('Name')"
        />

        <x-text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $category->name ?? '')"
            required
            autofocus
        />

        <x-input-error
            class="mt-2"
            :messages="$errors->get('name')"
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
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >{{ old('description', $category->description ?? '') }}</textarea>

        <x-input-error
            class="mt-2"
            :messages="$errors->get('description')"
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
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked((bool) $isActive)
            >

            <span class="ms-2 text-sm text-gray-700">
                {{ __('Active category') }}
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
                    ? __('Update Category')
                    : __('Create Category')
            }}
        </x-primary-button>

        <a
            href="{{ route('categories.index') }}"
            class="text-sm text-gray-600 hover:text-gray-900"
        >
            {{ __('Cancel') }}
        </a>
    </div>
</div>
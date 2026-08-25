@props([
    'resource',
    'title',
    'record' => null,
])

@php
    $editing = $record !== null;

    $action = $editing
        ? route($resource.'.update', $record)
        : route($resource.'.store');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __($title) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form
                        method="POST"
                        action="{{ $action }}"
                        class="space-y-6"
                    >
                        @csrf

                        @if ($editing)
                            @method('PUT')
                        @endif

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
                                :value="old('name', $record?->name ?? '')"
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
                                for="phone"
                                :value="__('Phone')"
                            />

                            <x-text-input
                                id="phone"
                                name="phone"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('phone', $record?->phone ?? '')"
                                required
                            />

                            <x-input-error
                                class="mt-2"
                                :messages="$errors->get('phone')"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="email"
                                :value="__('Email')"
                            />

                            <x-text-input
                                id="email"
                                name="email"
                                type="email"
                                class="mt-1 block w-full"
                                :value="old('email', $record?->email ?? '')"
                            />

                            <x-input-error
                                class="mt-2"
                                :messages="$errors->get('email')"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="address"
                                :value="__('Address')"
                            />

                            <textarea
                                id="address"
                                name="address"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >{{ old('address', $record?->address ?? '') }}</textarea>

                            <x-input-error
                                class="mt-2"
                                :messages="$errors->get('address')"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="notes"
                                :value="__('Notes')"
                            />

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >{{ old('notes', $record?->notes ?? '') }}</textarea>

                            <x-input-error
                                class="mt-2"
                                :messages="$errors->get('notes')"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>
                                {{
                                    $editing
                                        ? __('Save Changes')
                                        : __('Create')
                                }}
                            </x-primary-button>

                            <a
                                href="{{ route($resource.'.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
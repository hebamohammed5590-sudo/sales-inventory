@props([
    'resource',
    'title',
    'record',
])

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __($title) }}
            </h2>

            @can('update', $record)
                <a
                    href="{{ route($resource.'.edit', $record) }}"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="space-y-6 p-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Name') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $record->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Phone') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $record->phone }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Email') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $record->email ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ __('Address') }}
                            </p>

                            <p class="mt-1 text-gray-900">
                                {{ $record->address ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ __('Notes') }}
                        </p>

                        <p class="mt-1 text-gray-900">
                            {{ $record->notes ?? '-' }}
                        </p>
                    </div>

                    <a
                        href="{{ route($resource.'.index') }}"
                        class="inline-block text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
@props([
    'records',
    'resource',
    'title',
    'addLabel',
    'modelClass',
    'sort',
])

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __($title) }}
            </h2>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route($resource.'.export', request()->query()) }}"
                    class="rounded-md border border-green-600 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50"
                >
                    {{ __('Export CSV') }}
                </a>

                @can('create', $modelClass)
                    <a
                        href="{{ route($resource.'.create') }}"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        {{ __($addLabel) }}
                    </a>
                @endcan
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

            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <form
                    method="GET"
                    action="{{ route($resource.'.index') }}"
                    class="grid gap-4 md:grid-cols-3"
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
                            :placeholder="__('Name or phone')"
                        />
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
                                value="phone"
                                @selected($sort === 'phone')
                            >
                                {{ __('Phone') }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            {{ __('Search') }}
                        </x-primary-button>

                        <a
                            href="{{ route($resource.'.index') }}"
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
                                    {{ __('Name') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Phone') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Email') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($records as $record)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $record->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $record->phone }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $record->email ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-4">
                                            <a
                                                href="{{ route($resource.'.show', $record) }}"
                                                class="text-gray-600 hover:text-gray-900"
                                            >
                                                {{ __('View') }}
                                            </a>

                                            @can('update', $record)
                                                <a
                                                    href="{{ route($resource.'.edit', $record) }}"
                                                    class="text-indigo-600 hover:text-indigo-800"
                                                >
                                                    {{ __('Edit') }}
                                                </a>
                                            @endcan

                                            @can('delete', $record)
                                                <form
                                                    method="POST"
                                                    action="{{ route($resource.'.destroy', $record) }}"
                                                    onsubmit="return confirm('{{ __('Delete this record?') }}')"
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
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-4 py-6 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No records found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
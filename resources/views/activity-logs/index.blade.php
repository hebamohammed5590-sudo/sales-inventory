<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Activity Log') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('activity-logs.index') }}"
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
                            :value="$search"
                            :placeholder="__('Actor or description')"
                        />
                    </div>

                    <div>
                        <x-input-label
                            for="action"
                            :value="__('Action')"
                        />

                        <select
                            id="action"
                            name="action"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">
                                {{ __('All Actions') }}
                            </option>

                            @foreach ($actions as $availableAction)
                                <option
                                    value="{{ $availableAction }}"
                                    @selected($action === $availableAction)
                                >
                                    {{ __(str_replace('.', ' ', $availableAction)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            {{ __('Filter') }}
                        </x-primary-button>

                        <a
                            href="{{ route('activity-logs.index') }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Actor') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Action') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Description') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {{ __('Subject') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($activityLogs as $activityLog)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ $activityLog->created_at->format('Y-m-d H:i:s') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $activityLog->actor?->name ?? __('Deleted User') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800">
                                            {{ __(str_replace('.', ' ', $activityLog->action)) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $activityLog->description }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        @if ($activityLog->subject)
                                            {{ class_basename($activityLog->subject_type) }}
                                            #{{ $activityLog->subject_id }}
                                        @else
                                            {{ __('Unavailable') }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No activity found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($activityLogs->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $activityLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
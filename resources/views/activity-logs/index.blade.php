<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
            {{ __('Activity Log') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
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
                            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Actor') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Action') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Description') }}
                                </th>

                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('Subject') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                            @forelse ($activityLogs as $activityLog)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $activityLog->created_at->format('Y-m-d H:i:s') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $activityLog->actor?->name ?? __('Deleted User') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            {{ __(str_replace('.', ' ', $activityLog->action)) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $activityLog->description }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
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
                                        class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ __('No activity found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($activityLogs->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        {{ $activityLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
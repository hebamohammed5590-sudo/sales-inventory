<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                {{ __('Categories') }}
            </h2>

            <a
                href="{{ route('categories.create') }}"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
            >
                {{ __('Add Category') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-100 p-4 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-100 p-4 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-900">
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Name') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Description') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($categories as $category)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $category->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $category->description ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if ($category->is_active)
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                                {{ __('Active') }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                                {{ __('Inactive') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-4">
                                            <a
                                                href="{{ route('categories.edit', $category) }}"
                                                class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                {{ __('Edit') }}
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('categories.destroy', $category) }}"
                                                onsubmit="return confirm('{{ __('Delete this category?') }}')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
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
                                        colspan="4"
                                        class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ __('No categories found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $categories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
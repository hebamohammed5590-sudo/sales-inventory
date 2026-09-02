<nav
    x-data="{ open: false }"
    class="border-b border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900"
>
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-100" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link
                        :href="route('dashboard')"
                        :active="request()->routeIs('dashboard')"
                    >
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @can('viewAny', \App\Models\ProductReturn::class)
                        <x-nav-link
                            :href="route('product-returns.index')"
                            :active="request()->routeIs('product-returns.*')"
                        >
                            {{ __('Product Returns') }}
                        </x-nav-link>
                    @endcan

                    @can('viewAny', \App\Models\ActivityLog::class)
                        <x-nav-link
                            :href="route('activity-logs.index')"
                            :active="request()->routeIs('activity-logs.*')"
                        >
                            {{ __('Activity Log') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Search, Language and User Dropdown -->
            <div class="hidden sm:ms-6 sm:flex sm:items-center">
                <!-- Desktop Theme Toggle -->
                <button
                    type="button"
                    @click="$store.theme.toggle()"
                    class="me-3 inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    :aria-pressed="$store.theme.dark"
                >
                    <svg
                        x-show="!$store.theme.dark"
                        x-cloak
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364-.707-.707M6.343 6.343l-.707-.707m12.728 0-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>

                    <svg
                        x-show="$store.theme.dark"
                        x-cloak
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646a9.003 9.003 0 1011.708 11.708z"
                        />
                    </svg>

                    <span
                        x-show="!$store.theme.dark"
                        x-cloak
                    >
                        {{ __('Dark Mode') }}
                    </span>

                    <span
                        x-show="$store.theme.dark"
                        x-cloak
                    >
                        {{ __('Light Mode') }}
                    </span>
                </button>

                <form
                    method="GET"
                    action="{{ route('search.index') }}"
                    class="me-3"
                >
                    <div class="flex items-center gap-2">
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ __('Global Search') }}"
                            minlength="2"
                            maxlength="100"
                            required
                            class="w-56 rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
                        >

                        <button
                            type="submit"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            {{ __('Search') }}
                        </button>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route('locale.update', [
                        'locale' => app()->getLocale() === 'ar' ? 'en' : 'ar',
                    ]) }}"
                    class="me-3"
                >
                    @csrf

                    <button
                        type="submit"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                    </button>
                </form>

                <x-dropdown
                    align="right"
                    width="48"
                >
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            <div>
                                {{ Auth::user()->name }}
                            </div>

                            <div class="ms-1">
                                <svg
                                    class="h-4 w-4 fill-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                        >
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault();
                                    this.closest('form').submit();"
                            >
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 dark:focus:bg-gray-800 dark:focus:text-gray-200"
                >
                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            :class="{'hidden': open, 'inline-flex': ! open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16"
                        />

                        <path
                            :class="{'hidden': ! open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div
        :class="{'block': open, 'hidden': ! open}"
        class="hidden sm:hidden"
    >
        <div class="space-y-1 pb-3 pt-2">
            <!-- Mobile Toggle -->
            <div class="px-4 pb-3">
                <button
                    type="button"
                    @click="$store.theme.toggle()"
                    class="flex w-full items-center justify-between rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    :aria-pressed="$store.theme.dark"
                >
                    <span
                        x-show="!$store.theme.dark"
                        x-cloak
                    >
                        {{ __('Dark Mode') }}
                    </span>

                    <span
                        x-show="$store.theme.dark"
                        x-cloak
                    >
                        {{ __('Light Mode') }}
                    </span>

                    <svg
                        x-show="!$store.theme.dark"
                        x-cloak
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364-.707-.707M6.343 6.343l-.707-.707m12.728 0-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>

                    <svg
                        x-show="$store.theme.dark"
                        x-cloak
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646a9.003 9.003 0 1011.708 11.708z"
                        />
                    </svg>
                </button>
            </div>

            <form
                method="GET"
                action="{{ route('search.index') }}"
                class="px-4 pb-3"
            >
                <div class="flex gap-2">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="{{ __('Global Search') }}"
                        minlength="2"
                        maxlength="100"
                        required
                        class="block w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
                    >

                    <button
                        type="submit"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        {{ __('Search') }}
                    </button>
                </div>
            </form>

            <x-responsive-nav-link
                :href="route('dashboard')"
                :active="request()->routeIs('dashboard')"
            >
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @can('viewAny', \App\Models\ProductReturn::class)
                <x-responsive-nav-link
                    :href="route('product-returns.index')"
                    :active="request()->routeIs('product-returns.*')"
                >
                    {{ __('Product Returns') }}
                </x-responsive-nav-link>
            @endcan

            @can('viewAny', \App\Models\ActivityLog::class)
                <x-responsive-nav-link
                    :href="route('activity-logs.index')"
                    :active="request()->routeIs('activity-logs.*')"
                >
                    {{ __('Activity Log') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-800">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800 dark:text-gray-100">
                    {{ Auth::user()->name }}
                </div>

                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ Auth::user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault();
                            this.closest('form').submit();"
                    >
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
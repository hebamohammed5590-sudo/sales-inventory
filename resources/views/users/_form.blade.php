@php
    $editing = isset($user);

    $selectedRole = old(
        'role',
        $editing ? $user->role->value : 'cashier'
    );

    $isActive = old(
        'is_active',
        $editing ? $user->is_active : true
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
            :value="old('name', $user->name ?? '')"
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
            for="email"
            :value="__('Email')"
        />

        <x-text-input
            id="email"
            name="email"
            type="email"
            class="mt-1 block w-full"
            :value="old('email', $user->email ?? '')"
            required
        />

        <x-input-error
            class="mt-2"
            :messages="$errors->get('email')"
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
            :value="old('phone', $user->phone ?? '')"
        />

        <x-input-error
            class="mt-2"
            :messages="$errors->get('phone')"
        />
    </div>

    <div>
        <x-input-label
            for="role"
            :value="__('Role')"
        />

        <select
            id="role"
            name="role"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            @foreach ($roles as $role)
                <option
                    value="{{ $role->value }}"
                    @selected($selectedRole === $role->value)
                >
                    {{ __($role->label()) }}
                </option>
            @endforeach
        </select>

        <x-input-error
            class="mt-2"
            :messages="$errors->get('role')"
        />
    </div>

    <div>
        <x-input-label
            for="password"
            :value="__('Password')"
        />

        <x-text-input
            id="password"
            name="password"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
            :required="! $editing"
        />

        @if ($editing)
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Leave blank to keep the current password.') }}
            </p>
        @endif

        <x-input-error
            class="mt-2"
            :messages="$errors->get('password')"
        />
    </div>

    <div>
        <x-input-label
            for="password_confirmation"
            :value="__('Confirm Password')"
        />

        <x-text-input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
            :required="! $editing"
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
                {{ __('Active account') }}
            </span>
        </label>

        <x-input-error
            class="mt-2"
            :messages="$errors->get('is_active')"
        />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>
            {{ $editing ? __('Update User') : __('Create User') }}
        </x-primary-button>

        <a
            href="{{ route('users.index') }}"
            class="text-sm text-gray-600 hover:text-gray-900"
        >
            {{ __('Cancel') }}
        </a>
    </div>
</div>
<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && ($this->user()?->can('update', $user) ?? false);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'role' => [
                'required',
                Rule::enum(Role::class),
            ],

            'password' => [
                'nullable',
                'confirmed',
                Password::defaults(),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}

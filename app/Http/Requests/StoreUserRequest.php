<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('users.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Naam is verplicht.',
            'email.required' => 'E-mail is verplicht.',
            'email.email' => 'E-mail moet een geldig e-mailadres zijn.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'username.required' => 'Gebruikersnaam is verplicht.',
            'username.unique' => 'Deze gebruikersnaam is al in gebruik.',
            'password.required' => 'Wachtwoord is verplicht.',
            'password.min' => 'Wachtwoord moet minimaal 8 karakters lang zijn.',
            'password.confirmed' => 'Wachtwoord bevestiging komt niet overeen.',
            'first_name.required' => 'Voornaam is verplicht.',
            'last_name.required' => 'Achternaam is verplicht.',
        ];
    }
}

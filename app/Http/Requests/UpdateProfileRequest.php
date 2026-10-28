<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->student !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]+$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indique su nombre completo.',
            'name.max' => 'El nombre no puede superar los :max caracteres.',
            'email.required' => 'Indique su correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ese correo electrónico ya está en uso.',
            'telefono.regex' => 'El teléfono solo puede tener números, espacios y los símbolos + ( ) -.',
            'telefono.max' => 'El teléfono no puede superar los :max caracteres.',
            'direccion.max' => 'La dirección no puede superar los :max caracteres.',
            'ciudad.max' => 'La ciudad no puede superar los :max caracteres.',
        ];
    }
}

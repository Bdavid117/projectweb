<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Thesis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->student !== null
            && Thesis::where('student_id', $this->user()->student->id)->exists();
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'repositorio_url' => ['nullable', 'url:http,https', 'max:255'],
            'codirector_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', Role::Profesor->value)],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Indique el título del trabajo de grado.',
            'titulo.max' => 'El título no puede superar los :max caracteres.',
            'repositorio_url.url' => 'El repositorio debe ser un enlace http o https válido.',
            'repositorio_url.max' => 'El enlace del repositorio no puede superar los :max caracteres.',
            'codirector_id.exists' => 'El codirector seleccionado no es válido.',
        ];
    }
}

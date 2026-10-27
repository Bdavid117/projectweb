<?php

namespace App\Http\Requests;

use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->student !== null
            && Internship::where('student_id', $this->user()->student->id)->exists();
    }

    public function rules(): array
    {
        return [
            'jefe_nombre' => ['required', 'string', 'max:255'],
            'jefe_cargo' => ['nullable', 'string', 'max:255'],
            'actividades' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'jefe_nombre.required' => 'Indique el nombre del jefe inmediato.',
            'jefe_nombre.max' => 'El nombre del jefe no puede superar los :max caracteres.',
            'jefe_cargo.max' => 'El cargo no puede superar los :max caracteres.',
            'actividades.max' => 'Las actividades no pueden superar los :max caracteres.',
        ];
    }
}

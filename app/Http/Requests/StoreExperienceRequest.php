<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Experience::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string', 'min:10', 'max:2000'],
            'fecha_inicio' => ['required', 'date', 'before_or_equal:today'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio', 'before_or_equal:today'],
            'alcance' => ['nullable', 'string', 'max:255'],
            'institucion' => ['nullable', 'string', 'max:255'],
            'convenio' => ['nullable', 'string', 'max:255'],
            'docente_id' => ['nullable', 'exists:users,id'],
            'rol' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Indique el nombre de la experiencia.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los :max caracteres.',
            'descripcion.required' => 'Describa la experiencia.',
            'descripcion.min' => 'Describa la experiencia (mínimo :min caracteres).',
            'descripcion.max' => 'Las observaciones no pueden superar :max caracteres.',
            'fecha_inicio.required' => 'Indique la fecha de inicio.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede ser posterior a hoy.',
            'fecha_fin.required' => 'Indique la fecha de fin.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'fecha_fin.before_or_equal' => 'La fecha de fin no puede ser posterior a hoy.',
            'alcance.max' => 'El alcance no puede superar los :max caracteres.',
            'institucion.max' => 'La institución no puede superar los :max caracteres.',
            'convenio.max' => 'El convenio no puede superar los :max caracteres.',
        ];
    }
}
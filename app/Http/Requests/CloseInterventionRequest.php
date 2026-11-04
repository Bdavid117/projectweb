<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('intervencion'));
    }

    public function rules(): array
    {
        return [
            'resultado' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'resultado.required' => 'Describa el resultado de la intervención al cerrarla.',
            'resultado.min' => 'Describa el resultado (mínimo :min caracteres).',
            'resultado.max' => 'El resultado no puede superar :max caracteres.',
        ];
    }
}

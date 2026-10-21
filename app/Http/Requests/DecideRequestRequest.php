<?php

namespace App\Http\Requests;

use App\Services\RequestWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('solicitud'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(RequestWorkflow::DECISIONS)],
            'numero_acta' => ['required', 'string', 'max:30'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Seleccione la decisión del comité.',
            'decision.in' => 'La decisión seleccionada no es válida.',
            'numero_acta.required' => 'Indique el número de acta.',
            'numero_acta.max' => 'El número de acta no puede superar :max caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar :max caracteres.',
        ];
    }
}

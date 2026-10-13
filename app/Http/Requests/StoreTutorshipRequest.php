<?php

namespace App\Http\Requests;

use App\Models\Tutorship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTutorshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tutorship = $this->route('tutoria');

        return $tutorship
            ? $this->user()->can('update', $tutorship)
            : $this->user()->can('create', Tutorship::class);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'motivo' => ['required', Rule::in(config('program.tutorship_reasons'))],
            'observaciones' => ['required', 'string', 'min:10', 'max:2000'],
            'compromisos' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Indique la fecha de la sesión.',
            'fecha.date' => 'La fecha de la sesión no es válida.',
            'fecha.before_or_equal' => 'La fecha de la sesión no puede ser posterior a hoy.',
            'motivo.required' => 'Seleccione el motivo de la sesión.',
            'motivo.in' => 'Seleccione un motivo de la lista.',
            'observaciones.required' => 'Describa lo tratado en la sesión.',
            'observaciones.min' => 'Describa lo tratado en la sesión (mínimo :min caracteres).',
            'observaciones.max' => 'Las observaciones no pueden superar :max caracteres.',
            'compromisos.max' => 'Los compromisos no pueden superar :max caracteres.',
        ];
    }
}

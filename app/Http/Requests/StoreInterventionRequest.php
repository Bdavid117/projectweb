<?php

namespace App\Http\Requests;

use App\Models\Intervention;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Intervention::class, $this->route('student')]);
    }

    protected function prepareForValidation(): void
    {
        // La observación de un profesor no enlaza ningún registro.
        if ($this->input('origen') === 'observacion_profesor') {
            $this->merge(['origen_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'origen' => ['required', Rule::in(array_keys(Intervention::ORIGENES))],
            'origen_id' => ['nullable', 'integer', 'min:1'],
            'descripcion' => ['required', 'string', 'min:10', 'max:2000'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /** El registro de origen debe existir y pertenecer al mismo estudiante (aislamiento). */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $origen = $this->input('origen');
            $origenId = $this->input('origen_id');
            $class = Intervention::modelForOrigen($origen);

            if ($class === null || $origenId === null) {
                return;
            }

            /** @var Student $student */
            $student = $this->route('student');
            $belongs = $class::where('id', $origenId)->where('student_id', $student->id)->exists();

            if (! $belongs) {
                $validator->errors()->add('origen_id', 'El origen seleccionado no pertenece a este estudiante.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'origen.required' => 'Seleccione el origen de la intervención.',
            'origen.in' => 'Seleccione un origen de la lista.',
            'descripcion.required' => 'Describa la intervención de acompañamiento.',
            'descripcion.min' => 'Describa la intervención (mínimo :min caracteres).',
            'descripcion.max' => 'La descripción no puede superar :max caracteres.',
            'fecha.required' => 'Indique la fecha de la intervención.',
            'fecha.date' => 'La fecha de la intervención no es válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser posterior a hoy.',
        ];
    }
}

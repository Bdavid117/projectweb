<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\ExperienceType;
use App\Support\ExperienceFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $experience = $this->route('experiencia');

        return $experience
            ? $this->user()->can('update', $experience)
            : $this->user()->can('create', \App\Models\Experience::class);
    }

    public function rules(): array
    {
        $applicable = ExperienceFields::for(ExperienceType::find($this->input('experience_type_id'))?->categoria);
        $rules = $this->baseRules();

        foreach (ExperienceFields::OPTIONAL as $field) {
            if (! array_key_exists($field, $applicable)) {
                $rules[$field] = ['prohibited'];
            } elseif ($applicable[$field]) {
                array_unshift($rules[$field], 'required');
                $rules[$field] = array_values(array_diff($rules[$field], ['nullable']));
            }
        }

        return $rules;
    }

    /** Los campos que no aplican al tipo elegido no se guardan, aunque el navegador los envíe. */
    protected function prepareForValidation(): void
    {
        $applicable = ExperienceFields::for(ExperienceType::find($this->input('experience_type_id'))?->categoria);

        $this->merge(collect(ExperienceFields::OPTIONAL)
            ->reject(fn (string $field) => array_key_exists($field, $applicable))
            ->mapWithKeys(fn (string $field) => [$field => null])
            ->all());
    }

    private function baseRules(): array
    {
        return [
            'experience_type_id' => ['required', 'integer', 'exists:experience_types,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'fecha_inicio' => ['required', 'date', 'before_or_equal:today'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'alcance' => ['nullable', 'string', 'max:255'],
            'institucion' => ['nullable', 'string', 'max:255'],
            'convenio' => ['nullable', 'string', 'max:255'],
            'docente_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', Role::Profesor->value)],
            'rol' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'experience_type_id.required' => 'Seleccione el tipo de experiencia.',
            'experience_type_id.exists' => 'El tipo de experiencia no es válido.',
            'nombre.required' => 'Indique el nombre de la experiencia.',
            'nombre.max' => 'El nombre no puede superar los :max caracteres.',
            'descripcion.max' => 'La descripción no puede superar los :max caracteres.',
            'fecha_inicio.required' => 'Indique la fecha de inicio.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede ser posterior a hoy.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'alcance.max' => 'El alcance no puede superar los :max caracteres.',
            'institucion.max' => 'La institución no puede superar los :max caracteres.',
            'convenio.max' => 'El convenio no puede superar los :max caracteres.',
            'docente_id.exists' => 'El docente seleccionado no es válido.',
            'alcance.required' => 'Indique el alcance de la experiencia.',
            'institucion.required' => 'Indique la institución.',
            'docente_id.required' => 'Seleccione el docente responsable.',
            'rol.required' => 'Indique su rol en la experiencia.',
            'rol.max' => 'El rol no puede superar los :max caracteres.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CommitteeRequest::class);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'integer', 'exists:request_types,id'],
            'descripcion' => ['required', 'string', 'min:50', 'max:5000'],
            'soportes' => ['nullable', 'array', 'max:'.config('program.attachment_max_files')],
            'soportes.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('program.attachment_max_kb')],
            'codigo_estudiante' => [
                Rule::requiredIf(fn () => $this->user()->role === Role::Coordinacion),
                'nullable', 'string', 'exists:students,codigo',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Seleccione el tipo de solicitud antes de continuar.',
            'tipo.integer' => 'El tipo de solicitud seleccionado no es válido.',
            'tipo.exists' => 'El tipo de solicitud seleccionado no es válido.',
            'descripcion.required' => 'Describa el motivo de la solicitud.',
            'descripcion.min' => 'La descripción debe tener al menos :min caracteres.',
            'descripcion.max' => 'La descripción no puede superar :max caracteres.',
            'soportes.array' => 'Los soportes no tienen un formato válido.',
            'soportes.max' => 'Puede adjuntar hasta :max archivos.',
            'soportes.*.file' => 'No se pudo cargar el archivo; verifique que no supere 5 MB.',
            'soportes.*.uploaded' => 'No se pudo cargar el archivo; verifique que no supere 5 MB.',
            'soportes.*.mimes' => 'Los soportes deben ser PDF, JPG o PNG.',
            'soportes.*.max' => 'Cada soporte puede pesar máximo 5 MB.',
            'codigo_estudiante.required' => 'Indique el código del estudiante.',
            'codigo_estudiante.exists' => 'No existe un estudiante con ese código.',
        ];
    }
}

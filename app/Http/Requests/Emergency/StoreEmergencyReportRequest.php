<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RF-19: comparte las reglas del reporte de emergencia de pasajeros y conductores.
 */
class StoreEmergencyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(['accident', 'breakdown', 'medical', 'security', 'other']),
            ],
            'description' => ['required', 'string', 'max:500'],
            'bus_id' => ['nullable', 'integer', Rule::exists('buses', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Selecciona el tipo de emergencia.',
            'type.in' => 'El tipo de emergencia seleccionado no es válido.',
            'description.required' => 'Describe la emergencia antes de enviarla.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'bus_id.integer' => 'Selecciona una unidad válida.',
            'bus_id.exists' => 'La unidad seleccionada no existe.',
            'latitude.numeric' => 'La latitud debe ser un número válido.',
            'latitude.between' => 'La latitud está fuera de rango.',
            'latitude.required_with' => 'Indica también la latitud.',
            'longitude.numeric' => 'La longitud debe ser un número válido.',
            'longitude.between' => 'La longitud está fuera de rango.',
            'longitude.required_with' => 'Indica también la longitud.',
        ];
    }
}

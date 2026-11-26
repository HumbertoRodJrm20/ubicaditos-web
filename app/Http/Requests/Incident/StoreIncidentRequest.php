<?php

namespace App\Http\Requests\Incident;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HU-025: valida el reporte de incidencia y su contexto opcional.
 */
class StoreIncidentRequest extends FormRequest
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
            'category' => ['required', 'string', Rule::in(array_keys(Incident::CATEGORIES))],
            'description' => ['required', 'string', 'max:'.Incident::MAX_DESCRIPTION_LENGTH],
            'route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')],
            'bus_id' => ['nullable', 'integer', Rule::exists('buses', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Selecciona el tipo de incidencia.',
            'category.in' => 'El tipo de incidencia seleccionado no es válido.',
            'description.required' => 'Describe la incidencia antes de enviar el reporte.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los '
                .Incident::MAX_DESCRIPTION_LENGTH.' caracteres.',
            'route_id.integer' => 'Selecciona una ruta válida.',
            'route_id.exists' => 'La ruta seleccionada no existe.',
            'bus_id.integer' => 'Selecciona una unidad válida.',
            'bus_id.exists' => 'La unidad seleccionada no existe.',
            'latitude.numeric' => 'La latitud debe ser un número válido.',
            'latitude.between' => 'La latitud está fuera de rango.',
            'latitude.required_with' => 'Indica también la latitud.',
            'longitude.numeric' => 'La longitud debe ser un número válido.',
            'longitude.between' => 'La longitud está fuera de rango.',
            'longitude.required_with' => 'Indica también la longitud.',
            'is_public.boolean' => 'La opción de visibilidad pública no es válida.',
        ];
    }
}

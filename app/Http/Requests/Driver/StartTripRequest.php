<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-10: selección opcional de autobús y ruta al iniciar un viaje.
 */
class StartTripRequest extends FormRequest
{
    /**
     * Las reglas de rol se aplican en el middleware de la ruta.
     */
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
            'bus_id' => ['nullable', 'integer', 'exists:buses,id'],
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bus_id.integer' => 'El autobús seleccionado no es válido.',
            'bus_id.exists' => 'El autobús seleccionado no existe.',
            'route_id.integer' => 'La ruta seleccionada no es válida.',
            'route_id.exists' => 'La ruta seleccionada no existe.',
        ];
    }
}

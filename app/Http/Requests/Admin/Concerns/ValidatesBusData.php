<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Bus;

/**
 * Reglas compartidas por el alta y la edición administrativa de autobuses.
 */
trait ValidatesBusData
{
    /**
     * @return array<string, mixed>
     */
    protected function busRules(?Bus $bus = null): array
    {
        $plateRule = $bus === null
            ? 'required|string|size:6|unique:buses,license_plate|regex:/^[A-Z]{3}[0-9]{3}$/'
            : 'required|string|size:6|unique:buses,license_plate,'.$bus->id.'|regex:/^[A-Z]{3}[0-9]{3}$/';

        return [
            'license_plate' => $plateRule,
            'model' => 'required|string|in:Foton AUV,Yutong,Mercedes-Benz,Dina,Toyota,Honda',
            'capacity' => 'required|integer|min:30|max:50',
            'driver_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,maintenance',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function busMessages(): array
    {
        return [
            'license_plate.size' => 'La placa debe tener exactamente 6 caracteres',
            'license_plate.regex' => 'La placa debe tener el formato: 3 letras seguidas de 3 números (Ej: ABC123)',
            'model.in' => 'Debe seleccionar un modelo válido',
            'capacity.required' => 'Se necesita que se asigne capacidad',
            'capacity.integer' => 'La capacidad debe ser un número entero',
            'capacity.min' => 'La capacidad mínima es de 30 pasajeros',
            'capacity.max' => 'La capacidad máxima es de 50 pasajeros',
        ];
    }
}

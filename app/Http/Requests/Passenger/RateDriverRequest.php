<?php

namespace App\Http\Requests\Passenger;

use App\Models\Trip;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RNF-02: una calificación debe provenir de un viaje completado con el conductor.
 */
class RateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $driverId = (int) $this->route('driver');

        return $this->user()?->isPassenger()
            && User::whereKey($driverId)->where('role', 'driver')->exists();
    }

    /**
     * El formulario histórico no pide el viaje: se vincula automáticamente al
     * último viaje completado del conductor para no romper a los clientes
     * existentes. Si no existe ese viaje, la validación required lo rechaza.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('trip_id')) {
            return;
        }

        $trip = Trip::where('driver_id', $this->route('driver'))
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        if ($trip !== null) {
            $this->merge(['trip_id' => $trip->id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $driverId = (int) $this->route('driver');

        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'trip_id' => [
                'required',
                'integer',
                Rule::exists('trips', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($driverId): void {
                    $trip = Trip::find($value);

                    if ($trip === null
                        || $trip->status !== 'completed'
                        || (int) $trip->driver_id !== $driverId) {
                        $fail('Solo puedes calificar a un conductor después de un viaje completado con él.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Selecciona una calificación.',
            'rating.integer' => 'La calificación debe ser un número entero.',
            'rating.min' => 'La calificación mínima es 1.',
            'rating.max' => 'La calificación máxima es 5.',
            'comment.max' => 'El comentario no puede superar los 500 caracteres.',
            'trip_id.required' => 'No hay un viaje completado con este conductor que permita calificarlo.',
            'trip_id.integer' => 'El viaje seleccionado no es válido.',
            'trip_id.exists' => 'El viaje seleccionado no existe.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncRouteStopsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stops' => ['required', 'array'],
            'stops.*' => ['integer', 'exists:stops,id', 'distinct'],
            'minutes' => ['sometimes', 'array'],
            'minutes.*' => ['integer', 'min:0', 'max:600'],
        ];
    }

    public function messages(): array
    {
        return [
            'stops.distinct' => 'Una parada no puede repetirse en la secuencia.',
            'stops.*.exists' => 'Una de las paradas seleccionadas no existe.',
        ];
    }
}

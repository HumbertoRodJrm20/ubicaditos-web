<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30', 'unique:routes,name'],
            'code' => ['required', 'string', 'max:20', 'unique:routes,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'estimated_duration' => ['nullable', 'integer', 'min:40', 'max:90'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no puede tener más de 30 caracteres.',
            'name.unique' => 'Este nombre de ruta ya existe.',
            'code.unique' => 'Este código de ruta ya existe.',
            'color.regex' => 'El color debe ser un valor hexadecimal como #3B82F6.',
            'estimated_duration.min' => 'La duración estimada debe ser de al menos 40 minutos.',
            'estimated_duration.max' => 'La duración estimada no puede exceder los 90 minutos.',
        ];
    }
}

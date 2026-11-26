<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesBusData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del alta administrativa de autobuses.
 */
class StoreBusRequest extends FormRequest
{
    use ValidatesBusData;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->busRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->busMessages();
    }
}

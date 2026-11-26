<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesBusData;
use App\Models\Bus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la edición administrativa de autobuses.
 */
class UpdateBusRequest extends FormRequest
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
        /** @var Bus $bus */
        $bus = $this->route('bus');

        return $this->busRules($bus);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->busMessages();
    }
}

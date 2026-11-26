<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesUserData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del alta administrativa de usuarios.
 */
class StoreUserRequest extends FormRequest
{
    use ValidatesUserData;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->userRules(isStore: true);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->userMessages();
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesUserData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la edición administrativa de usuarios.
 */
class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return $this->userRules($user);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->userMessages();
    }
}

<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Extiende la actualización de perfil con idioma y preferencias in-app.
 */
class SettingsUpdateRequest extends ProfileUpdateRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'locale' => ['sometimes', 'nullable', Rule::in(User::LOCALES)],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.emergency' => ['sometimes', 'boolean'],
            'notification_preferences.incident' => ['sometimes', 'boolean'],
            'notification_preferences.location_share' => ['sometimes', 'boolean'],
        ]);
    }
}

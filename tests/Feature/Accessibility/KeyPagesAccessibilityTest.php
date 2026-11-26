<?php

namespace Tests\Feature\Accessibility;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Caracterización de accesibilidad estructural en las páginas clave (RNF-15/HU-033).
 */
class KeyPagesAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_declares_language_labels_and_theme_toggle(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang="es"', false)
            ->assertSee('for="email"', false)
            ->assertSee('for="password"', false)
            ->assertSee('aria-label="Cambiar entre modo claro y oscuro"', false)
            ->assertSee('dark:bg-slate-950', false);
    }

    public function test_passenger_map_labels_filters_and_icon_controls(): void
    {
        $passenger = User::factory()->create();

        $this->actingAs($passenger)->get(route('passenger.dashboard'))
            ->assertOk()
            ->assertSee('for="route-filter"', false)
            ->assertSee('aria-label="Filtros"', false)
            ->assertSee('aria-label="Acercar"', false)
            ->assertSee('aria-label="Alejar"', false)
            ->assertSee('aria-label="Mi ubicación"', false)
            ->assertSee('aria-label="Compartir mi ubicación"', false)
            ->assertSee('dark:bg-gray-800', false);
    }

    public function test_profile_labels_settings_and_exposes_logout_confirmation(): void
    {
        $passenger = User::factory()->create();

        $this->actingAs($passenger)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('for="locale"', false)
            ->assertSee('Sí, cerrar sesión')
            ->assertSee('dark:bg-gray-700', false);
    }
}

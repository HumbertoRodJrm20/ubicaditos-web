<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modo oscuro compartido: inicialización sin destello y toggle reutilizable.
 */
class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_include_theme_initializer_and_toggle(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ubicaditos-theme', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('data-theme-icon="moon"', false)
            ->assertSee('data-theme-icon="sun"', false)
            ->assertSee('UbicaditosTheme', false);
    }

    public function test_passenger_layout_includes_shared_theme_toggle(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($passenger)
            ->get(route('passenger.menu'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('UbicaditosTheme', false);
    }

    public function test_admin_layout_includes_shared_theme_toggle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.buses.index'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('UbicaditosTheme', false);
    }
}

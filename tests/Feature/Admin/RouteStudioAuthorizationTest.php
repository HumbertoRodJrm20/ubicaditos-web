<?php

namespace Tests\Feature\Admin;

use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Control de acceso del Estudio de rutas y paradas y de los endpoints
 * administrativos de escritura de rutas y paradas.
 */
class RouteStudioAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Endpoints que solo un administrador debe poder invocar.
     *
     * Se construye dentro de cada prueba porque necesita la aplicación
     * iniciada para generar las URL.
     *
     * @return array<int, array{0: string, 1: string}> pares [método HTTP, URL]
     */
    private function protectedEndpoints(): array
    {
        $route = Route::create([
            'name' => 'Ruta Estudio',
            'code' => 'RS-1',
            'color' => '#F59E0B',
            'is_active' => true,
        ]);

        $stop = Stop::create([
            'name' => 'Parada Estudio',
            'address' => 'Calle de prueba 1',
            'latitude' => 17.0654,
            'longitude' => -96.7256,
            'is_active' => true,
        ]);

        return [
            ['GET', route('admin.routes.manage')],
            ['POST', route('admin.routes.store')],
            ['PUT', route('admin.routes.update', $route)],
            ['DELETE', route('admin.routes.destroy', $route)],
            ['POST', route('admin.stops.store')],
            ['PUT', route('admin.stops.update', $stop)],
            ['DELETE', route('admin.stops.destroy', $stop)],
        ];
    }

    public function test_guests_are_redirected_to_login_on_every_endpoint(): void
    {
        foreach ($this->protectedEndpoints() as [$method, $url]) {
            $response = $this->call($method, $url);

            $this->assertSame(
                route('login'),
                $response->headers->get('Location'),
                "Un invitado debe ser redirigido al login en {$method} {$url}"
            );
        }
    }

    public function test_non_admin_roles_are_forbidden_on_every_endpoint(): void
    {
        $endpoints = $this->protectedEndpoints();

        foreach (['driver', 'passenger'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($endpoints as [$method, $url]) {
                $response = $this->actingAs($user)->call($method, $url);

                $this->assertSame(
                    403,
                    $response->getStatusCode(),
                    "El rol {$role} debe recibir 403 en {$method} {$url}"
                );
            }
        }
    }

    public function test_admin_can_open_the_studio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.routes.manage'))
            ->assertOk()
            ->assertViewIs('admin.routes.manage');
    }

    public function test_studio_url_is_not_captured_by_the_route_resource(): void
    {
        $this->assertSame('/admin/routes/manage', route('admin.routes.manage', [], false));

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/routes/manage')
            ->assertOk()
            ->assertViewIs('admin.routes.manage');
    }
}

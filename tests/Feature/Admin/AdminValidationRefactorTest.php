<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Requests\Admin\StoreBusRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateBusRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Caracterización de las validaciones administrativas antes de extraer Form Requests.
 */
class AdminValidationRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_controller_uses_dedicated_form_requests(): void
    {
        $this->assertSame(
            StoreUserRequest::class,
            (new ReflectionMethod(UserController::class, 'store'))->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            UpdateUserRequest::class,
            (new ReflectionMethod(UserController::class, 'update'))->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_bus_controller_uses_dedicated_form_requests(): void
    {
        $this->assertSame(
            StoreBusRequest::class,
            (new ReflectionMethod(BusController::class, 'store'))->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            UpdateBusRequest::class,
            (new ReflectionMethod(BusController::class, 'update'))->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_user_validation_errors_keep_their_keys_and_spanish_messages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Ana',
            'last_name' => 'López',
            'maternal_last_name' => 'García',
            'email' => 'correo-invalido',
            'role' => 'driver',
            'phone' => '123',
        ])->assertSessionHasErrors([
            'email',
            'work_shift',
            'phone',
        ]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Ana',
            'last_name' => 'López',
            'maternal_last_name' => 'García',
            'email' => 'ana.valida@example.com',
            'role' => 'passenger',
            'phone' => '9511234567',
        ])->assertSessionHasErrors('password');

        $user = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'first_name' => 'Ana',
            'last_name' => 'López',
            'maternal_last_name' => 'García',
            'email' => 'correo-invalido',
            'role' => 'passenger',
            'phone' => '123',
        ])->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_bus_validation_errors_keep_their_keys_and_spanish_messages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.buses.store'), [
            'license_plate' => 'abc123',
            'model' => 'Modelo inexistente',
            'capacity' => 10,
            'status' => 'emergency',
        ])->assertSessionHasErrors([
            'license_plate',
            'model',
            'capacity',
            'status',
        ]);

        $this->actingAs($admin)->post(route('admin.buses.store'), [
            'license_plate' => 'ABC123',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
        ])->assertRedirect(route('admin.buses.index'));

        $this->actingAs($admin)->post(route('admin.buses.store'), [
            'license_plate' => 'ABC123',
            'model' => 'Yutong',
            'capacity' => 40,
            'status' => 'active',
        ])->assertSessionHasErrors('license_plate');
    }
}

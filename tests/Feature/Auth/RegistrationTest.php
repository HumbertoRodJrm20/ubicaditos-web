<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.email_verification_enabled' => true]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_as_passengers_by_default(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'maternal_last_name' => 'Example',
            'phone' => '9511234567',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'passenger',
        ]);
    }

    public function test_new_users_can_register_with_hash_symbol_in_password(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'maternal_last_name' => 'Example',
            'phone' => '9511234567',
            'email' => 'hash@example.com',
            'password' => 'Password1#',
            'password_confirmation' => 'Password1#',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'hash@example.com',
            'role' => 'passenger',
        ]);
    }

    public function test_new_users_can_register_with_underscore_in_password(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'maternal_last_name' => 'Example',
            'phone' => '9511234567',
            'email' => 'underscore@example.com',
            'password' => 'Password1_',
            'password_confirmation' => 'Password1_',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'underscore@example.com',
            'role' => 'passenger',
        ]);
    }

    public function test_registration_rejects_passwords_missing_each_requirement(): void
    {
        foreach ([
            ['Pass1!', 'Pass1!'],
            ['12345678!', '12345678!'],
            ['Password!', 'Password!'],
            ['Password1', 'Password1'],
            ['Password1#', 'Password1!'],
            ['Password 1#', 'Password 1#'],
        ] as [$password, $confirmation]) {
            $response = $this->post('/register', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'maternal_last_name' => 'Example',
                'phone' => '9511234567',
                'email' => 'invalid-password@example.com',
                'password' => $password,
                'password_confirmation' => $confirmation,
            ]);

            $response->assertSessionHasErrors('password');
            $this->assertGuest();
        }
    }

    public function test_registration_rejects_invalid_data(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'maternal_last_name' => '',
            'phone' => '123',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['maternal_last_name', 'phone', 'email', 'password']);
        $this->assertGuest();
    }
}

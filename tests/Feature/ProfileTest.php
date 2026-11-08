<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_form_exposes_and_prefills_all_personal_fields(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'López',
            'maternal_last_name' => 'García',
            'phone' => '9511234567',
            'email' => 'ana@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('name="maternal_last_name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('value="Ana"', false)
            ->assertSee('value="López"', false)
            ->assertSee('value="García"', false)
            ->assertSee('value="9511234567"', false)
            ->assertSee('value="ana@example.com"', false)
            ->assertSee('Apellido materno')
            ->assertSee('Teléfono');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'maternal_last_name' => 'Example',
                'phone' => '9511234567',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('Example', $user->maternal_last_name);
        $this->assertSame('9511234567', $user->phone);
        $this->assertSame('Test User Example', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_phone_must_be_ten_digits_when_updating_the_profile(): void
    {
        $user = User::factory()->create(['phone' => '9511234567']);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'maternal_last_name' => 'Example',
                'phone' => '123',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasErrors('phone')
            ->assertRedirect('/profile');

        $this->assertSame('9511234567', $user->fresh()->phone);
    }

    public function test_maternal_last_name_is_required_when_updating_the_profile(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'maternal_last_name' => '',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasErrors('maternal_last_name')
            ->assertRedirect('/profile');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'maternal_last_name' => 'Example',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
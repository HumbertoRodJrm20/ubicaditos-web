<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'maternal_last_name' => fake()->lastName(),
            'phone' => fake()->numerify('951#######'),
            'role' => 'passenger',
            'approval_status' => User::APPROVAL_APPROVED,
            'is_active' => true,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'driver',
            'approval_status' => User::APPROVAL_APPROVED,
            'work_shift' => 'morning',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => User::APPROVAL_PENDING,
        ]);
    }

    public function rejectedApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => User::APPROVAL_REJECTED,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'must_change_password' => true,
        ]);
    }
}

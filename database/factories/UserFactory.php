<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username'         => fake()->unique()->userName(),
            'display_name'     => fake()->name(),
            'email'            => fake()->unique()->safeEmail(),
            'password'         => bcrypt('password'),
            'role'             => 'player',
            'state'            => 1,
            'activation_key'   => Str::random(32),
            'activated'        => true,
            'disabled'         => false,
            'first_time_login' => false,
            'theme'            => 'darkred',
            'tooltips_enabled' => true,
        ];
    }
}

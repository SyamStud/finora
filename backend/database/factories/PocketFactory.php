<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pocket>
 */
class PocketFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'account_id' => Account::factory()->for($user),
            'name' => fake()->randomElement(['Jajan', 'Kebutuhan', 'Tabungan Liburan', 'Kecil-kecilan']),
            'icon' => 'wallet',
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Proxy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proxy>
 */
class ProxyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $protocols = ['https', 'http', 'socks5', 'socks4'];
        $countries = ['DE', 'NL', 'US', 'PL', 'FR', 'GB', 'SG', 'JP', 'CA', 'BR'];

        return [
            'address' => fake()->ipv4(),
            'port' => fake()->numberBetween(80, 65535),
            'protocol' => fake()->randomElement($protocols),
            'country' => fake()->randomElement($countries),
            'google_pass' => fake()->boolean(70),
            'cloudflare_pass' => fake()->boolean(60),
            'latency_ms' => fake()->numberBetween(30, 800),
            'last_checked_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'is_active' => fake()->boolean(80),
        ];
    }
}

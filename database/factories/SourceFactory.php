<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'FreeProxyList', 'Geonode API', 'ProxyScrape', 'OpenProxy Space',
            'ProxyNova', 'Spys.one', 'HideMy.name', 'ProxyList Plus',
        ];

        return [
            'name' => fake()->randomElement($names).' #'.fake()->unique()->randomNumber(3),
            'url' => fake()->url(),
            'is_active' => fake()->boolean(80),
            'is_enabled' => fake()->boolean(70),
            'last_scraped_at' => fake()->optional(0.7)->dateTimeBetween('-1 hour', 'now'),
            'proxy_count' => fake()->numberBetween(0, 500),
            'last_error' => fake()->optional(0.2)->sentence(),
        ];
    }
}

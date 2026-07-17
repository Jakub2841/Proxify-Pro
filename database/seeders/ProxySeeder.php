<?php

namespace Database\Seeders;

use App\Models\Proxy;
use Illuminate\Database\Seeder;

class ProxySeeder extends Seeder
{
    public function run(): void
    {
        Proxy::factory(50)->create();
    }
}

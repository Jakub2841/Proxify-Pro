<?php

namespace App\Console\Commands;

use App\Jobs\CheckProxies;
use App\Models\Proxy;
use Illuminate\Console\Command;

class CheckProxiesCommand extends Command
{
    protected $signature = 'check:proxies';

    protected $description = 'Dispatch proxy checking jobs';

    public function handle(): int
    {
        $proxies = Proxy::stale()->count();

        if ($proxies === 0) {
            $this->info('No proxies to check.');

            return self::SUCCESS;
        }

        CheckProxies::dispatch();

        $this->info("{$proxies} proxies queued for checking.");

        return self::SUCCESS;
    }
}

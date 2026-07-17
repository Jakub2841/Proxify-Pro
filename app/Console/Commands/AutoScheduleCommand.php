<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class AutoScheduleCommand extends Command
{
    protected $signature = 'auto:schedule';

    protected $description = 'Run scheduled scrape and check based on settings';

    public function handle(): int
    {
        if (Setting::get('scrape_enabled', true)) {
            $this->runIfDue('scrape:sources', 'last_auto_scrape', Setting::get('scrape_interval', 30));
        }

        if (Setting::get('check_enabled', true)) {
            $this->runIfDue('check:proxies', 'last_auto_check', Setting::get('check_interval', 15));
        }

        return self::SUCCESS;
    }

    private function runIfDue(string $command, string $cacheKey, int $intervalMinutes): void
    {
        $lastRun = Cache::get($cacheKey);

        if ($lastRun !== null && now()->diffInMinutes($lastRun) < $intervalMinutes) {
            return;
        }

        Cache::put($cacheKey, now()->toIso8601String(), now()->addHours(2));

        Artisan::call($command);
    }
}

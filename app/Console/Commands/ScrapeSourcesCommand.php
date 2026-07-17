<?php

namespace App\Console\Commands;

use App\Actions\ScrapeSource;
use App\Models\Source;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('scrape:sources')]
#[Description('Scrape all enabled proxy sources')]
class ScrapeSourcesCommand extends Command
{
    public function handle(ScrapeSource $scrapeSource): int
    {
        $sources = Source::where('is_enabled', true)->get();

        if ($sources->isEmpty()) {
            $this->info('No enabled sources found.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($sources as $source) {
            try {
                $inserted = $scrapeSource($source);
                $total += $inserted;
                $this->info("{$source->name}: {$inserted} new proxies");
            } catch (\Throwable $e) {
                $source->update(['last_error' => $e->getMessage()]);
                $this->error("{$source->name}: {$e->getMessage()}");
            }
        }

        Cache::put('last_auto_scrape', now()->toIso8601String(), now()->addHours(2));

        $this->info("Done. {$total} new proxies added from {$sources->count()} sources.");

        return self::SUCCESS;
    }
}

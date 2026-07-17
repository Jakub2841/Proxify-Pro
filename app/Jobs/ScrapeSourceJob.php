<?php

namespace App\Jobs;

use App\Actions\ScrapeSource;
use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScrapeSourceJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Source $source) {}

    public function uniqueId(): string
    {
        return (string) $this->source->id;
    }

    public function handle(ScrapeSource $scrapeSource): void
    {
        $scrapeSource($this->source);
    }
}

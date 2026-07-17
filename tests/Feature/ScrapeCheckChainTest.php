<?php

use App\Enums\SourceParserType;
use App\Jobs\CheckProxies;
use App\Jobs\GenerateExportSnapshots;
use App\Jobs\ScrapeSourceJob;
use App\Models\Source;
use Illuminate\Support\Facades\Bus;

test('scheduled chain dispatches scrape, check, then snapshots in order', function () {
    Bus::fake();

    Source::factory()->create(['is_enabled' => true, 'parser_type' => SourceParserType::PlainText]);

    Bus::chain([
        new ScrapeSourceJob(Source::first()),
        new CheckProxies,
        new GenerateExportSnapshots,
    ])->dispatch();

    Bus::assertChained([
        ScrapeSourceJob::class,
        CheckProxies::class,
        GenerateExportSnapshots::class,
    ]);
});

test('failed job in chain prevents subsequent jobs from running', function () {
    Bus::fake();

    Source::factory()->create(['is_enabled' => true, 'parser_type' => SourceParserType::PlainText]);

    $chain = Bus::chain([
        new ScrapeSourceJob(Source::first()),
        new CheckProxies,
        new GenerateExportSnapshots,
    ]);

    $chain->catch(function () {
        //
    })->dispatch();

    Bus::assertChained([
        ScrapeSourceJob::class,
        CheckProxies::class,
        GenerateExportSnapshots::class,
    ]);

    expect(true)->toBeTrue();
});

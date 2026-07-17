<?php

namespace App\Actions;

use App\Enums\Protocol;
use App\Enums\SourceParserType;
use App\Models\Proxy;
use App\Models\Source;
use App\Services\Parsers\JsonApiParser;
use App\Services\Parsers\PlainTextListParser;
use Illuminate\Support\Facades\Http;

final class ScrapeSource
{
    public function __invoke(Source $source): int
    {
        $response = Http::timeout(30)->get($source->url)->throw();

        $proxies = match ($source->parser_type) {
            SourceParserType::PlainText => $this->parsePlainText($response->body(), $source),
            SourceParserType::JsonApi => $this->parseJson($response->body(), $source),
            default => [],
        };

        $inserted = 0;

        if ($proxies !== []) {
            $inserted = $this->upsertProxies($proxies);
        }

        $source->update([
            'last_scraped_at' => now(),
            'proxy_count' => $inserted,
            'last_error' => null,
        ]);

        return $inserted;
    }

    /**
     * @param  list<array{address: string, port: int, protocol: Protocol, anonymity?: string}>  $proxies
     */
    private function upsertProxies(array $proxies): int
    {
        // Deduplicate within the parsed batch (same source may list a proxy twice).
        $unique = [];
        foreach ($proxies as $p) {
            $key = $p['address'].':'.$p['port'];
            $unique[$key] = $p;
        }
        $proxies = array_values($unique);

        // Single flat query: fetch all existing rows for the addresses in this batch.
        // Using whereIn avoids SQLite's "expression tree too large" error
        // that comes from deeply nested OR WHERE chains.
        $addresses = array_unique(array_column($proxies, 'address'));

        $existing = Proxy::whereIn('address', $addresses)
            ->get(['address', 'port'])
            ->mapWithKeys(fn (Proxy $p) => [$p->address.':'.$p->port => true])
            ->toArray();

        $rows = [];
        foreach ($proxies as $p) {
            if (isset($existing[$p['address'].':'.$p['port']])) {
                continue;
            }

            $rows[] = [
                'address' => $p['address'],
                'port' => $p['port'],
                'protocol' => $p['protocol'],
                'anonymity' => $p['anonymity'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $inserted = count($rows);

        foreach (array_chunk($rows, 500) as $chunk) {
            Proxy::insert($chunk);
        }

        return $inserted;
    }

    /**
     * @return list<array{address: string, port: int, protocol: Protocol}>
     */
    private function parsePlainText(string $body, Source $source): array
    {
        return app(PlainTextListParser::class)->parse($body, $source->default_protocol);
    }

    /**
     * @return list<array{address: string, port: int, protocol: Protocol, anonymity?: string}>
     */
    private function parseJson(string $body, Source $source): array
    {
        return app(JsonApiParser::class)->parse($body, $source->default_protocol);
    }
}

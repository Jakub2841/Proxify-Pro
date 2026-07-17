<?php

namespace App\Actions\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportProxies
{
    private function formatAddress($proxy, string $dataFormat): string
    {
        $address = $proxy->address.':'.$proxy->port;

        return $dataFormat === 'protocol_ip_port'
            ? $proxy->protocol->value.'://'.$address
            : $address;
    }

    public function toCsv(Builder $query, string $dataFormat = 'ip_port'): StreamedResponse
    {
        return Response::streamDownload(function () use ($query, $dataFormat) {
            $handle = fopen('php://output', 'w');

            $query->chunkById(500, function ($proxies) use ($handle, $dataFormat) {
                foreach ($proxies as $proxy) {
                    fputcsv($handle, [$this->formatAddress($proxy, $dataFormat)]);
                }
            });

            fclose($handle);
        }, 'proxies.csv', ['Content-Type' => 'text/csv']);
    }

    public function toTxt(Builder $query, string $dataFormat = 'ip_port'): StreamedResponse
    {
        return Response::streamDownload(function () use ($query, $dataFormat) {
            $handle = fopen('php://output', 'w');

            $query->chunkById(500, function ($proxies) use ($handle, $dataFormat) {
                foreach ($proxies as $proxy) {
                    fwrite($handle, $this->formatAddress($proxy, $dataFormat).PHP_EOL);
                }
            });

            fclose($handle);
        }, 'proxies.txt', ['Content-Type' => 'text/plain']);
    }

    public function toJson(Builder $query, string $dataFormat = 'ip_port'): StreamedResponse
    {
        return Response::streamDownload(function () use ($query, $dataFormat) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, '[');

            $first = true;

            $query->chunkById(500, function ($proxies) use ($handle, &$first, $dataFormat) {
                foreach ($proxies as $proxy) {
                    if (! $first) {
                        fwrite($handle, ',');
                    }
                    $first = false;

                    fwrite($handle, json_encode([
                        'address' => $this->formatAddress($proxy, $dataFormat),
                        'protocol' => $proxy->protocol->value,
                    ], JSON_UNESCAPED_SLASHES));
                }
            });

            fwrite($handle, ']');
            fclose($handle);
        }, 'proxies.json', ['Content-Type' => 'application/json']);
    }
}

<?php

namespace App\Actions;

use App\DataTransferObjects\DetectedTableConfig;
use App\Services\SourceDetection\HtmlTableColumnDetector;
use Illuminate\Support\Facades\Http;

final class DetectSourceColumns
{
    /**
     * Fetch a source URL and attempt to detect its HTML table column layout.
     *
     * HTTP exceptions (timeout, non-2xx via ->throw()) propagate to the
     * caller — catching belongs in the Livewire component, consistent
     * with the existing DetectSourceFormat usage pattern.
     */
    public function __invoke(string $url): ?DetectedTableConfig
    {
        $response = Http::timeout(15)->get($url)->throw();

        return HtmlTableColumnDetector::detect($response->body());
    }
}

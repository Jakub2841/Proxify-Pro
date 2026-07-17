<?php

namespace App\Actions;

use App\Enums\SourceParserType;
use App\Services\SourceDetection\FormatDetector;
use Illuminate\Support\Facades\Http;

final class DetectSourceFormat
{
    public function __invoke(string $url): SourceParserType
    {
        $response = Http::timeout(15)->get($url)->throw();

        return FormatDetector::detect($response->body());
    }
}

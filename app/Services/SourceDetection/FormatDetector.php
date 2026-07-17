<?php

namespace App\Services\SourceDetection;

use App\Enums\SourceParserType;

final class FormatDetector
{
    public static function detect(string $raw): SourceParserType
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return SourceParserType::PlainText;
        }

        if (self::looksLikeHtml($trimmed)) {
            return SourceParserType::HtmlTable;
        }

        if (self::looksLikeJson($trimmed)) {
            return SourceParserType::JsonApi;
        }

        return SourceParserType::PlainText;
    }

    private static function looksLikeHtml(string $sample): bool
    {
        $chunk = substr($sample, 0, 1000);

        return (bool) preg_match('/<html|<head|<body|<table|<tr|<td|<th|<tbody|<thead/i', $chunk);
    }

    private static function looksLikeJson(string $sample): bool
    {
        $firstChar = $sample[0];

        if ($firstChar !== '{' && $firstChar !== '[') {
            return false;
        }

        json_decode($sample);

        return json_last_error() === JSON_ERROR_NONE;
    }
}

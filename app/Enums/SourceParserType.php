<?php

namespace App\Enums;

enum SourceParserType: string
{
    case PlainText = 'plain_text';
    case HtmlTable = 'html_table';
    case JsonApi = 'json_api';

    public function label(): string
    {
        return match ($this) {
            self::PlainText => __('Plain text'),
            self::HtmlTable => __('HTML table'),
            self::JsonApi => __('JSON API'),
        };
    }

    public function isSupported(): bool
    {
        return match ($this) {
            self::PlainText, self::JsonApi => true,
            self::HtmlTable => false,
        };
    }
}

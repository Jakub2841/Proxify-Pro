<?php

namespace App\Enums;

enum SortOption: string
{
    case LatencyAsc = 'latency_asc';
    case LatencyDesc = 'latency_desc';
    case LastCheckedDesc = 'last_checked_desc';

    public function label(): string
    {
        return match ($this) {
            self::LatencyAsc => __('Latency (lowest first)'),
            self::LatencyDesc => __('Latency (highest first)'),
            self::LastCheckedDesc => __('Last checked (newest first)'),
        };
    }
}

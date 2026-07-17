<?php

namespace App\Enums;

enum CleanupPeriod: int
{
    case SevenDays = 7;
    case FourteenDays = 14;
    case ThirtyDays = 30;
    case NinetyDays = 90;
    case Never = 0;

    public function label(): string
    {
        return match ($this) {
            self::SevenDays => __('7 days'),
            self::FourteenDays => __('14 days'),
            self::ThirtyDays => __('30 days'),
            self::NinetyDays => __('90 days'),
            self::Never => __('Never'),
        };
    }
}

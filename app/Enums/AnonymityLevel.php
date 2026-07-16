<?php

namespace App\Enums;

enum AnonymityLevel: string
{
    case Elite = 'elite';
    case Anonymous = 'anonymous';
    case Transparent = 'transparent';

    public function label(): string
    {
        return match ($this) {
            self::Elite => __('Elite'),
            self::Anonymous => __('Anonymous'),
            self::Transparent => __('Transparent'),
        };
    }
}

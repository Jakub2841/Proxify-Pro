<?php

namespace App\Enums;

enum Check: string
{
    case Google = 'google';
    case Cloudflare = 'cloudflare';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Google => __('Google pass'),
            self::Cloudflare => __('Cloudflare pass'),
            self::None => __('No checks'),
        };
    }
}

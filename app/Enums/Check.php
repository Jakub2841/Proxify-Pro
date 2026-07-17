<?php

namespace App\Enums;

enum Check: string
{
    case Google = 'google';
    case Cloudflare = 'cloudflare';

    public function label(): string
    {
        return match ($this) {
            self::Google => __('Google pass'),
            self::Cloudflare => __('Cloudflare pass'),
        };
    }
}

<?php

namespace App\Enums;

enum Protocol: string
{
    case Https = 'https';
    case Http = 'http';
    case Socks5 = 'socks5';
    case Socks4 = 'socks4';

    public function label(): string
    {
        return match ($this) {
            self::Https => __('HTTPS'),
            self::Http => __('HTTP'),
            self::Socks5 => __('SOCKS5'),
            self::Socks4 => __('SOCKS4'),
        };
    }
}

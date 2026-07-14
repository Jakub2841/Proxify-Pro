<?php

namespace App\Enums;

enum Protocol: string
{
    case Socks4 = 'socks4';
    case Socks5 = 'socks5';
    case Http = 'http';
    case Https = 'https';

    public function label(): string
    {
        return match ($this) {
            self::Socks4 => __('SOCKS4'),
            self::Socks5 => __('SOCKS5'),
            self::Http => __('HTTP'),
            self::Https => __('HTTPS'),
        };
    }
}

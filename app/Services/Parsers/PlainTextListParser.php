<?php

namespace App\Services\Parsers;

use App\Enums\Protocol;

final class PlainTextListParser
{
    /**
     * Matches an IPv4 address: four dot-separated octets, each 0–255.
     */
    private const IPV4_REGEX = '/^(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)$/';

    /**
     * Parse a raw plain-text proxy list.
     *
     * Each non-empty line is expected to contain one proxy in one of these formats:
     *   - protocol://ip:port
     *   - ip:port  (falls back to $defaultProtocol)
     *
     * Lines that don't contain a valid IPv4 address and port are silently skipped.
     *
     * @return list<array{address: string, port: int, protocol: Protocol}>
     */
    public function parse(string $raw, ?Protocol $defaultProtocol = null): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $raw));
        $proxies = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parsed = $this->parseLine($line, $defaultProtocol);

            if ($parsed !== null) {
                $proxies[] = $parsed;
            }
        }

        return $proxies;
    }

    /**
     * @return null|array{address: string, port: int, protocol: Protocol}
     */
    private function parseLine(string $line, ?Protocol $defaultProtocol): ?array
    {
        // Try protocol://ip:port format
        if (preg_match('#^(socks4|socks5|http|https)://(.+)#i', $line, $matches)) {
            $protocol = Protocol::from(strtolower($matches[1]));
            $rest = $matches[2];
        } else {
            $protocol = $defaultProtocol;
            $rest = $line;
        }

        if ($protocol === null) {
            return null; // no protocol embedded and no default set
        }

        $parts = explode(':', $rest);
        $port = (int) array_pop($parts);
        $address = implode(':', $parts); // support IPv6 in the future

        if ($address === '' || $port < 1 || $port > 65535) {
            return null;
        }

        if (! $this->isIpv4($address)) {
            return null;
        }

        return [
            'address' => $address,
            'port' => $port,
            'protocol' => $protocol,
        ];
    }

    private function isIpv4(string $value): bool
    {
        return preg_match(self::IPV4_REGEX, $value) === 1;
    }
}

<?php

namespace App\Services\Parsers;

use App\Enums\Protocol;

final class JsonApiParser
{
    private const IPV4_REGEX = '/^(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)$/';

    /**
     * Parse a raw JSON proxy API response.
     *
     * Recursively walks the entire decoded JSON tree looking for proxy objects.
     * Handles any nesting structure — paginated chunks, wrapped responses,
     * root arrays — without needing to know the specific key names in advance.
     *
     * Each proxy object may use:
     *   - "proxy": a pre-formatted "protocol://ip:port" string
     *   - "ip"/"address" + "port" + optional "protocol" and "anonymity"
     *
     * @return list<array{address: string, port: int, protocol: Protocol, anonymity?: string}>
     */
    public function parse(string $raw, ?Protocol $defaultProtocol = null): array
    {
        $data = json_decode($raw, true);

        if (! is_array($data)) {
            return [];
        }

        $proxies = [];

        $this->walk($data, $proxies, $defaultProtocol);

        return $proxies;
    }

    /**
     * Recursively walk a JSON structure and collect proxy entries.
     *
     * @param  list<array{address: string, port: int, protocol: Protocol, anonymity?: string}>  &$proxies
     */
    private function walk(mixed $node, array &$proxies, ?Protocol $defaultProtocol): void
    {
        if (! is_array($node)) {
            return;
        }

        // Try to extract a proxy from this node (associative array).
        if ($this->isProxyNode($node)) {
            $parsed = $this->extractProxy($node, $defaultProtocol);

            if ($parsed !== null) {
                $proxies[] = $parsed;
            }

            return; // don't recurse into a proxy node
        }

        // Recurse into all children.
        foreach ($node as $child) {
            $this->walk($child, $proxies, $defaultProtocol);
        }
    }

    /**
     * An associative array that has the shape of a proxy entry.
     */
    private function isProxyNode(array $node): bool
    {
        return isset($node['proxy'])
            || (isset($node['ip']) || isset($node['address'])) && isset($node['port']);
    }

    /**
     * @return null|array{address: string, port: int, protocol: Protocol, anonymity?: string}
     */
    private function extractProxy(array $node, ?Protocol $defaultProtocol): ?array
    {
        // Prefer the pre-formatted "proxy" string: "protocol://ip:port"
        if (isset($node['proxy']) && is_string($node['proxy'])) {
            return $this->parseProxyString($node['proxy'], $defaultProtocol, $node['anonymity'] ?? null);
        }

        // Fall back to ip/address + port fields.
        return $this->parseFields($node, $defaultProtocol);
    }

    /**
     * Parse a "protocol://ip:port" string, delegating to the same regex
     * used by PlainTextListParser for consistency.
     *
     * @return null|array{address: string, port: int, protocol: Protocol, anonymity?: string}
     */
    private function parseProxyString(string $proxy, ?Protocol $defaultProtocol, ?string $anonymity): ?array
    {
        if (preg_match('#^(socks4|socks5|http|https)://(.+)#i', $proxy, $m)) {
            $protocol = Protocol::from(strtolower($m[1]));
            $rest = $m[2];
        } elseif ($defaultProtocol !== null) {
            $protocol = $defaultProtocol;
            $rest = $proxy;
        } else {
            return null;
        }

        $parts = explode(':', $rest);
        $port = (int) array_pop($parts);
        $address = implode(':', $parts);

        if ($address === '' || $port < 1 || $port > 65535 || ! $this->isIpv4($address)) {
            return null;
        }

        $result = [
            'address' => $address,
            'port' => $port,
            'protocol' => $protocol,
        ];

        if ($anonymity !== null) {
            $result['anonymity'] = $anonymity;
        }

        return $result;
    }

    /**
     * Parse individual ip/address + port fields.
     *
     * @return null|array{address: string, port: int, protocol: Protocol, anonymity?: string}
     */
    private function parseFields(array $node, ?Protocol $defaultProtocol): ?array
    {
        $address = $node['ip'] ?? $node['address'] ?? null;

        if (! is_string($address) || ! $this->isIpv4($address)) {
            return null;
        }

        $port = $node['port'] ?? null;

        if (! is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
            return null;
        }

        $protocol = $defaultProtocol;

        if (isset($node['protocol']) && is_string($node['protocol'])) {
            $p = Protocol::tryFrom(strtolower($node['protocol']));

            if ($p !== null) {
                $protocol = $p;
            }
        }

        if ($protocol === null) {
            return null;
        }

        $result = [
            'address' => $address,
            'port' => (int) $port,
            'protocol' => $protocol,
        ];

        if (isset($node['anonymity']) && is_string($node['anonymity'])) {
            $result['anonymity'] = $node['anonymity'];
        }

        return $result;
    }

    private function isIpv4(string $value): bool
    {
        return preg_match(self::IPV4_REGEX, $value) === 1;
    }
}

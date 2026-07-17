<?php

use App\Enums\Protocol;
use App\Services\Parsers\JsonApiParser;

test('parses proxies from the proxies key', function () {
    $json = json_encode([
        'proxies' => [
            ['ip' => '192.168.1.1', 'port' => 8080, 'protocol' => 'socks5', 'anonymity' => 'elite'],
            ['ip' => '10.0.0.1', 'port' => 3128, 'protocol' => 'http', 'anonymity' => 'anonymous'],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toHaveCount(2);
    expect($result[0])->toMatchArray([
        'address' => '192.168.1.1', 'port' => 8080, 'protocol' => Protocol::Socks5, 'anonymity' => 'elite',
    ]);
    expect($result[1])->toMatchArray([
        'address' => '10.0.0.1', 'port' => 3128, 'protocol' => Protocol::Http, 'anonymity' => 'anonymous',
    ]);
});

test('parses proxies from the data key', function () {
    $json = json_encode([
        'data' => [
            ['ip' => '172.16.0.1', 'port' => 9999],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toHaveCount(1);
    expect($result[0]['address'])->toBe('172.16.0.1');
});

test('parses root-level array', function () {
    $json = json_encode([
        ['ip' => '192.168.1.1', 'port' => 80],
        ['ip' => '10.0.0.1', 'port' => 443],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toHaveCount(2);
});

test('accepts address as alias for ip', function () {
    $json = json_encode([
        'proxies' => [
            ['address' => '8.8.8.8', 'port' => 443, 'protocol' => 'https'],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result[0]['address'])->toBe('8.8.8.8');
    expect($result[0]['protocol'])->toBe(Protocol::Https);
});

test('falls back to default protocol when not in item', function () {
    $json = json_encode([
        'proxies' => [
            ['ip' => '192.168.1.1', 'port' => 8080],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Socks5);

    expect($result[0]['protocol'])->toBe(Protocol::Socks5);
});

test('skips items with invalid ip', function () {
    $json = json_encode([
        'proxies' => [
            ['ip' => '300.300.300.300', 'port' => 8080],
            ['ip' => '192.168.1.1', 'port' => 3128],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toHaveCount(1);
    expect($result[0]['address'])->toBe('192.168.1.1');
});

test('skips items with invalid port', function () {
    $json = json_encode([
        'proxies' => [
            ['ip' => '192.168.1.1', 'port' => 99999],
            ['ip' => '10.0.0.1', 'port' => 3128],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toHaveCount(1);
});

test('returns empty for invalid json', function () {
    $result = (new JsonApiParser)->parse('not json', Protocol::Http);

    expect($result)->toBe([]);
});

test('returns empty for object without recognized list key', function () {
    $json = json_encode(['unrelated' => 'data']);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toBe([]);
});

test('flattens nested arrays (pagination chunks)', function () {
    $json = json_encode([
        'proxies' => [
            [['ip' => '192.168.1.1', 'port' => 80], ['ip' => '10.0.0.1', 'port' => 443]],
            [['ip' => '172.16.0.1', 'port' => 3128]],
            ['ip' => '8.8.8.8', 'port' => 53], // mix of flat items and sub-arrays
        ],
    ]);

    $result = (new JsonApiParser)->parse($json, Protocol::Http);

    expect($result)->toHaveCount(4);
});

test('returns empty when no default protocol and item has none', function () {
    $json = json_encode([
        'proxies' => [
            ['ip' => '192.168.1.1', 'port' => 8080],
        ],
    ]);

    $result = (new JsonApiParser)->parse($json);

    expect($result)->toBe([]);
});

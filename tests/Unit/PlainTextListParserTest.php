<?php

use App\Enums\Protocol;
use App\Services\Parsers\PlainTextListParser;

test('parses bare ip:port lines with default protocol', function () {
    $raw = "192.168.1.1:8080\n10.0.0.1:3128\n172.16.0.1:9999";

    $result = (new PlainTextListParser)->parse($raw, Protocol::Http);

    expect($result)->toHaveCount(3);
    expect($result[0])->toBe(['address' => '192.168.1.1', 'port' => 8080, 'protocol' => Protocol::Http]);
    expect($result[1])->toBe(['address' => '10.0.0.1', 'port' => 3128, 'protocol' => Protocol::Http]);
    expect($result[2])->toBe(['address' => '172.16.0.1', 'port' => 9999, 'protocol' => Protocol::Http]);
});

test('parses protocol://ip:port lines, ignoring default protocol', function () {
    $raw = "socks5://192.168.1.1:1080\nhttps://10.0.0.1:443";

    $result = (new PlainTextListParser)->parse($raw, Protocol::Http);

    expect($result)->toHaveCount(2);
    expect($result[0]['protocol'])->toBe(Protocol::Socks5);
    expect($result[1]['protocol'])->toBe(Protocol::Https);
});

test('skips invalid lines', function () {
    $raw = "192.168.1.1:8080\nnot-a-proxy\ninvalid\n300.300.300.300:8080\n10.0.0.1:99999";

    $result = (new PlainTextListParser)->parse($raw, Protocol::Http);

    expect($result)->toHaveCount(1);
    expect($result[0]['address'])->toBe('192.168.1.1');
});

test('skips empty lines', function () {
    $raw = "\n\n192.168.1.1:8080\n\n\n10.0.0.1:3128\n\n";

    $result = (new PlainTextListParser)->parse($raw, Protocol::Http);

    expect($result)->toHaveCount(2);
});

test('returns empty array when no default protocol and no embedded protocol', function () {
    $raw = "192.168.1.1:8080\n10.0.0.1:3128";

    $result = (new PlainTextListParser)->parse($raw);

    expect($result)->toBe([]);
});

test('handles DOS line endings', function () {
    $raw = "192.168.1.1:8080\r\n10.0.0.1:3128\r\n172.16.0.1:9999";

    $result = (new PlainTextListParser)->parse($raw, Protocol::Http);

    expect($result)->toHaveCount(3);
});

test('returns empty array for empty input', function () {
    $result = (new PlainTextListParser)->parse('', Protocol::Http);

    expect($result)->toBe([]);
});

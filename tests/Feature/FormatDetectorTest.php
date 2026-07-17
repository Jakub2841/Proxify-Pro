<?php

use App\Enums\SourceParserType;
use App\Services\SourceDetection\FormatDetector;

test('plain ip:port string is detected as PlainText', function () {
    $raw = "192.168.1.1:8080\n10.0.0.1:3128\n172.16.0.1:9999";

    $result = FormatDetector::detect($raw);

    expect($result)->toBe(SourceParserType::PlainText);
});

test('full HTML document is detected as HtmlTable', function () {
    $raw = '<!DOCTYPE html><html><head><title>Proxies</title></head><body><table><thead><tr><th>IP</th><th>Port</th></tr></thead><tbody><tr><td>192.168.1.1</td><td>8080</td></tr></tbody></table></body></html>';

    $result = FormatDetector::detect($raw);

    expect($result)->toBe(SourceParserType::HtmlTable);
});

test('bare HTML fragment with no doctype is detected as HtmlTable', function () {
    $raw = '<table><tr><td>192.168.1.1</td><td>8080</td></tr></table>';

    $result = FormatDetector::detect($raw);

    expect($result)->toBe(SourceParserType::HtmlTable);
});

test('valid JSON object is detected as JsonApi', function () {
    $raw = '{"proxies":[{"ip":"192.168.1.1","port":8080},{"ip":"10.0.0.1","port":3128}]}';

    $result = FormatDetector::detect($raw);

    expect($result)->toBe(SourceParserType::JsonApi);
});

test('string starting with { but invalid JSON is detected as PlainText', function () {
    $raw = '{not valid json at all';

    $result = FormatDetector::detect($raw);

    expect($result)->toBe(SourceParserType::PlainText);
});

test('empty string is detected as PlainText', function () {
    $raw = '';

    $result = FormatDetector::detect($raw);

    expect($result)->toBe(SourceParserType::PlainText);
});

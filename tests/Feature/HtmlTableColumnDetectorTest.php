<?php

use App\Services\SourceDetection\HtmlTableColumnDetector;

function makeTableHtml(
    array $rows,
    ?string $id = null,
    ?string $class = null,
    bool $withWrapper = true,
): string {
    $attrs = '';
    if ($id !== null) {
        $attrs .= sprintf(' id="%s"', $id);
    }
    if ($class !== null) {
        $attrs .= sprintf(' class="%s"', $class);
    }

    $thead = '<thead><tr><th>IP Address</th><th>Port</th><th>Country</th></tr></thead>';

    $tbody = '<tbody>';
    foreach ($rows as $row) {
        $tbody .= '<tr>';
        foreach ($row as $cell) {
            $tbody .= sprintf('<td>%s</td>', $cell);
        }
        $tbody .= '</tr>';
    }
    $tbody .= '</tbody>';

    $table = sprintf('<table%s>%s%s</table>', $attrs, $thead, $tbody);

    if ($withWrapper) {
        return "<!DOCTYPE html><html><head><title>Test</title></head><body>{$table}</body></html>";
    }

    return $table;
}

// ---------------------------------------------------------------------------
// 1. Clean table with id, address in col 0, port in col 1
// ---------------------------------------------------------------------------

test('detects table with id, address col 0, port col 1', function () {
    $html = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080', 'US'],
            ['10.0.0.1', '3128', 'DE'],
            ['172.16.0.1', '9999', 'JP'],
            ['8.8.8.8', '443', 'US'],
        ],
        id: 'proxylist',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->rowSelector)->toBe('table#proxylist tr');
    expect($result->addressCol)->toBe(0);
    expect($result->portCol)->toBe(1);
    expect($result->confidence)->toBeGreaterThan(0.7);
});

// ---------------------------------------------------------------------------
// 2. Table with class attribute instead of id
// ---------------------------------------------------------------------------

test('detects table with class attribute', function () {
    $html = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080', 'US'],
            ['10.0.0.1', '3128', 'DE'],
            ['172.16.0.1', '9999', 'JP'],
            ['8.8.8.8', '443', 'US'],
        ],
        class: 'proxies striped',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->rowSelector)->toBe('table.proxies tr');
    expect($result->addressCol)->toBe(0);
    expect($result->portCol)->toBe(1);
});

// ---------------------------------------------------------------------------
// 2b. Class selector skips generic Bootstrap/utility classes
// ---------------------------------------------------------------------------

test('skips generic utility classes, preferring a specific class token', function () {
    $html = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080', 'US'],
            ['10.0.0.1', '3128', 'DE'],
            ['172.16.0.1', '9999', 'JP'],
            ['8.8.8.8', '443', 'US'],
        ],
        class: 'table proxylist',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->rowSelector)->toBe('table.proxylist tr');
});

test('falls back to first token when all classes are generic', function () {
    $html = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080', 'US'],
            ['10.0.0.1', '3128', 'DE'],
            ['172.16.0.1', '9999', 'JP'],
            ['8.8.8.8', '443', 'US'],
        ],
        class: 'table table-striped table-bordered',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->rowSelector)->toBe('table.table tr');
});

// ---------------------------------------------------------------------------
// 3. No id or class — second table on page → nth-of-type fallback
// ---------------------------------------------------------------------------

test('falls back to nth-of-type selector for table without id or class', function () {
    // First table is a decoy with low-confidence data.
    $decoy = makeTableHtml(
        rows: [
            ['random', 'text', 'here'],
            ['nope', 'nada', 'nothing'],
            ['foo', 'bar', 'baz'],
        ],
        withWrapper: false,
    );

    $target = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080', 'US'],
            ['10.0.0.1', '3128', 'DE'],
            ['172.16.0.1', '9999', 'JP'],
        ],
        withWrapper: false,
    );

    $html = "<!DOCTYPE html><html><body>{$decoy}{$target}</body></html>";

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->rowSelector)->toBe('table:nth-of-type(2) tr');
    expect($result->addressCol)->toBe(0);
    expect($result->portCol)->toBe(1);
});

// ---------------------------------------------------------------------------
// 4. Swapped columns — port in col 0, address in col 1
// ---------------------------------------------------------------------------

test('detects swapped columns where port comes before address', function () {
    $html = makeTableHtml(
        rows: [
            ['8080', '192.168.1.1', 'US'],
            ['3128', '10.0.0.1', 'DE'],
            ['9999', '172.16.0.1', 'JP'],
            ['443', '8.8.8.8', 'US'],
        ],
        id: 'swapped',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->addressCol)->toBe(1);
    expect($result->portCol)->toBe(0);
});

// ---------------------------------------------------------------------------
// 5. Fewer than 3 data rows → null
// ---------------------------------------------------------------------------

test('returns null when table has fewer than three data rows', function () {
    $html = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080'],
            ['10.0.0.1', '3128'],
        ],
        id: 'tiny',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// 6. No column reaches confidence threshold → null
// ---------------------------------------------------------------------------

test('returns null when no column meets confidence threshold', function () {
    $html = makeTableHtml(
        rows: [
            ['lorem', 'ipsum', 'dolor'],
            ['sit', 'amet', 'consectetur'],
            ['adipiscing', 'elit', 'sed'],
            ['do', 'eiusmod', 'tempor'],
        ],
        id: 'random',
    );

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// 7. Two tables — low-confidence loses, high-confidence wins
// ---------------------------------------------------------------------------

test('picks the higher-confidence table when multiple tables exist', function () {
    $lowConfidence = makeTableHtml(
        rows: [
            ['192.168.1.1', '8080', 'US'],
            ['not-an-ip', '3128', 'DE'],
            ['also-not', 'bad', 'JP'],
        ],
        id: 'bad',
        withWrapper: false,
    );

    $highConfidence = makeTableHtml(
        rows: [
            ['10.0.0.1', '3128', 'DE'],
            ['172.16.0.1', '9999', 'JP'],
            ['8.8.8.8', '443', 'US'],
            ['1.1.1.1', '80', 'AU'],
        ],
        id: 'good',
        withWrapper: false,
    );

    $html = "<!DOCTYPE html><html><body>{$lowConfidence}{$highConfidence}</body></html>";

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->rowSelector)->toBe('table#good tr');
});

// ---------------------------------------------------------------------------
// 8. Zero <table> elements → null (no crash)
// ---------------------------------------------------------------------------

test('returns null when page has no table elements', function () {
    $html = '<!DOCTYPE html><html><body><p>No tables here.</p></body></html>';

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// 9. Irregular row widths — extra cell row excluded from scoring
// ---------------------------------------------------------------------------

test('excludes irregular-width rows from scoring without crashing', function () {
    $html = '<!DOCTYPE html><html><body><table id="messy">'
        .'<thead><tr><th>IP</th><th>Port</th></tr></thead>'
        .'<tbody>'
        .'<tr><td>192.168.1.1</td><td>8080</td></tr>'
        .'<tr><td>10.0.0.1</td><td>3128</td></tr>'
        .'<tr><td>extra</td><td>cell</td><td>here</td></tr>' // irregular — 3 cells
        .'<tr><td>172.16.0.1</td><td>9999</td></tr>'
        .'<tr><td>8.8.8.8</td><td>443</td></tr>'
        .'</tbody></table></body></html>';

    $result = HtmlTableColumnDetector::detect($html);

    expect($result)->not->toBeNull();
    expect($result->addressCol)->toBe(0);
    expect($result->portCol)->toBe(1);
    // Confidence should be high because the irregular row was excluded.
    expect($result->confidence)->toBeGreaterThan(0.7);
});

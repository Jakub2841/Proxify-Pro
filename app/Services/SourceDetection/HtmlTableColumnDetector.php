<?php

namespace App\Services\SourceDetection;

use App\DataTransferObjects\DetectedTableConfig;
use Symfony\Component\DomCrawler\Crawler;

final class HtmlTableColumnDetector
{
    private const MIN_DATA_ROWS = 3;

    private const MIN_CONFIDENCE = 0.7;

    /**
     * Matches an IPv4 address: four dot-separated octets, each 0–255.
     */
    private const IPV4_REGEX = '/^(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)$/';

    public static function detect(string $raw): ?DetectedTableConfig
    {
        $crawler = new Crawler($raw);
        $tables = $crawler->filter('table');

        if ($tables->count() === 0) {
            return null;
        }

        $bestConfig = null;
        $bestConfidence = 0.0;

        $tables->each(function (Crawler $table, int $index) use (&$bestConfig, &$bestConfidence): void {
            $rows = self::extractDataRows($table);

            if (count($rows) < self::MIN_DATA_ROWS) {
                return;
            }

            $colCount = self::columnCount($rows);

            if ($colCount < 2) {
                return;
            }

            $ipRatios = [];
            $portRatios = [];

            for ($col = 0; $col < $colCount; $col++) {
                [$ipRatio, $portRatio] = self::scoreColumn($rows, $col);
                $ipRatios[$col] = $ipRatio;
                $portRatios[$col] = $portRatio;
            }

            $addressCol = (int) array_search(max($ipRatios), $ipRatios, true);

            $portCol = self::pickBestPortColumn($portRatios, $addressCol, $colCount);

            if ($portCol === null) {
                return;
            }

            $confidence = ($ipRatios[$addressCol] + $portRatios[$portCol]) / 2.0;

            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $oneBasedIndex = $index + 1;

                $bestConfig = new DetectedTableConfig(
                    rowSelector: self::buildRowSelector($table, $oneBasedIndex),
                    addressCol: $addressCol,
                    portCol: $portCol,
                    confidence: round($confidence, 4),
                );
            }
        });

        if ($bestConfig === null || $bestConfidence < self::MIN_CONFIDENCE) {
            return null;
        }

        return $bestConfig;
    }

    /** @return list<list<string>> */
    private static function extractDataRows(Crawler $table): array
    {
        $rows = [];

        $table->filter('tr')->each(function (Crawler $tr) use (&$rows): void {
            $cells = $tr->filter('td');
            if ($cells->count() === 0) {
                return;
            }

            $row = [];
            $cells->each(function (Crawler $td) use (&$row): void {
                $row[] = trim($td->text());
            });

            $rows[] = $row;
        });

        return $rows;
    }

    private static function columnCount(array $rows): int
    {
        return count($rows[0]);
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{float, float}
     */
    private static function scoreColumn(array $rows, int $colIndex): array
    {
        $ipHits = 0;
        $portHits = 0;
        $total = 0;

        $expectedWidth = count($rows[0]);

        foreach ($rows as $row) {
            if (count($row) !== $expectedWidth) {
                continue;
            }

            $cell = $row[$colIndex];
            $total++;

            if (self::isIpv4($cell)) {
                $ipHits++;
            }

            if (self::isPort($cell)) {
                $portHits++;
            }
        }

        return [
            $total > 0 ? $ipHits / $total : 0.0,
            $total > 0 ? $portHits / $total : 0.0,
        ];
    }

    private static function isIpv4(string $value): bool
    {
        return preg_match(self::IPV4_REGEX, $value) === 1;
    }

    private static function isPort(string $value): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $port = (int) $value;

        return $port >= 1 && $port <= 65535;
    }

    private static function pickBestPortColumn(array $portRatios, int $excludeCol, int $colCount): ?int
    {
        $bestCol = null;
        $bestRatio = -1.0;

        for ($col = 0; $col < $colCount; $col++) {
            if ($col === $excludeCol) {
                continue;
            }

            if ($portRatios[$col] > $bestRatio) {
                $bestRatio = $portRatios[$col];
                $bestCol = $col;
            }
        }

        return $bestCol;
    }

    private const GENERIC_CLASS_TOKENS = [
        'table', 'striped', 'bordered', 'hover', 'responsive', 'sm',
        'condensed', 'datatable', 'dataTable', 'display', 'sortable',
        'table-striped', 'table-bordered', 'table-hover', 'table-responsive',
        'table-condensed', 'table-sm',
    ];

    private static function pickBestClass(string $classAttr): string
    {
        $tokens = array_filter(
            array_map('trim', explode(' ', $classAttr)),
            static fn (string $t): bool => $t !== '',
        );

        $specific = array_filter(
            $tokens,
            static fn (string $t): bool => ! in_array(strtolower($t), self::GENERIC_CLASS_TOKENS, true),
        );

        if ($specific !== []) {
            return array_values($specific)[0];
        }

        return $tokens[0];
    }

    /**
     * Selector priority: id → class → nth-of-type.
     *
     * When multiple classes are present on the table, generic/utility
     * class names (see GENERIC_CLASS_TOKENS) are skipped in favor of a
     * more specific one. A table made entirely of generic classes falls
     * back to its first class token.
     *
     * The eventual HtmlTableParser must filter for rows containing <td>
     * (the selector is intentionally broad for CssSelector compatibility).
     */
    private static function buildRowSelector(Crawler $table, int $oneBasedIndex): string
    {
        $id = $table->attr('id');

        if ($id !== null && $id !== '') {
            return sprintf('table#%s tr', $id);
        }

        $class = $table->attr('class');

        if ($class !== null && $class !== '') {
            $chosenClass = self::pickBestClass(trim($class));

            return sprintf('table.%s tr', $chosenClass);
        }

        return sprintf('table:nth-of-type(%d) tr', $oneBasedIndex);
    }
}

<?php

namespace App\Services\Pdf;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\Process\Process;

class NOCWellExtractor
{
    private const REPORT_DATE_MODE = 'first_header'; // Options: first_header, page_header

    private const STANDARD_COLUMNS = [
        'field_name' => [47.0, 108.0],
        'well_name' => [108.0, 170.0],
        'rig_name' => [170.0, 233.0],
        'objective' => [233.0, 336.0],
        'spud_date' => [336.0, 375.0],
        'target_depth' => [375.0, 414.0],
        'progress' => [414.0, 452.0],
        'current_depth' => [452.0, 491.0],
        'budget' => [491.0, 540.0],
        'cumulative_cost' => [540.0, 593.0],
        'summary' => [593.0, 842.0],
    ];

    private const GROUPED_COLUMNS = [
        'field_name' => [47.0, 108.0],
        'well_name' => [108.0, 170.0],
        'rig_name' => [170.0, 233.0],
        'objective' => [233.0, 299.0],
        'spud_date' => [299.0, 344.0],
        'target_depth' => [344.0, 383.0],
        'progress' => [383.0, 430.0],
        'current_depth' => [430.0, 478.0],
        'budget' => [478.0, 540.0],
        'cumulative_cost' => [540.0, 598.0],
        'summary' => [598.0, 842.0],
    ];

    private const LEFT_INDENTED_COLUMNS = [
        'field_name' => [90.0, 165.0],
        'well_name' => [165.0, 225.0],
        'rig_name' => [225.0, 275.0],
        'objective' => [275.0, 360.0],
        'spud_date' => [360.0, 423.0],
        'target_depth' => [423.0, 465.0],
        'progress' => [465.0, 490.0],
        'current_depth' => [490.0, 530.0],
        'budget' => [530.0, 584.0],
        'cumulative_cost' => [584.0, 637.0],
        'summary' => [637.0, 842.0],
    ];

    public function extract(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("NOC drilling PDF not found: {$filePath}");
        }

        $pages = $this->readPositionedPages($filePath);
        $records = [];
        $documentReportDate = null;
        $currentCompany = null;
        $currentColumns = self::STANDARD_COLUMNS;

        foreach ($pages as &$page) {
            $page['lines'] = $this->buildLines($page['words']);
            $page['report_date'] = $this->extractReportDate($page['lines']);
            if ($documentReportDate === null && $page['report_date'] !== null) {
                $documentReportDate = $page['report_date'];
            }
        }
        unset($page);

        if ($documentReportDate === null) {
            throw new RuntimeException('Report Date was not found inside the NOC drilling PDF.');
        }

        foreach ($pages as $page) {
            $lines = $page['lines'];
            $effectiveReportDate = self::REPORT_DATE_MODE === 'page_header'
                ? ($page['report_date'] ?? $documentReportDate)
                : $documentReportDate;

            $headerBands = $this->findHeaderBands(
                $lines,
                $page['words'],
                $page['width'],
                $page['rectangles']
            );
            $ignoredBands = $this->findIgnoredBands($lines);
            $companyMarkers = $this->findCompanyMarkers($lines);
            $rowAnchors = $this->findRowAnchors(
                $page['words'],
                $headerBands,
                $page['height'],
                $page['rectangles']
            );
            foreach ($rowAnchors as $index => $anchor) {
                $company = $this->companyAt($anchor['y'], $companyMarkers, $currentCompany);
                if ($company === null) {
                    continue;
                }

                $upper = $this->rowUpperBoundary($index, $rowAnchors, $companyMarkers, $headerBands);
                $lower = $this->rowLowerBoundary(
                    $index,
                    $rowAnchors,
                    $companyMarkers,
                    $headerBands,
                    $page['height']
                );

                $record = $this->extractRow(
                    $page['words'],
                    $this->columnsForRow($anchor, $headerBands, $currentColumns),
                    $upper,
                    $lower,
                    $headerBands,
                    $ignoredBands,
                    $companyMarkers,
                    $page['width'],
                    $anchor
                );

                if ($record['well_name'] === null) {
                    continue;
                }

                $record['company_name'] = $company;
                $record['report_date'] = $effectiveReportDate;
                $records[] = $record;
            }

            if ($companyMarkers !== []) {
                $currentCompany = end($companyMarkers)['company'];
            }

            if ($headerBands !== []) {
                $currentColumns = end($headerBands)['columns'];
            }
        }

        return $records;
    }

    private function readPositionedPages(string $filePath): array
    {
        $process = new Process(['pdftotext', '-bbox-layout', $filePath, '-']);
        $process->setTimeout(120);
        $process->mustRun();

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($process->getOutput(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new RuntimeException('Unable to parse positioned text from the NOC drilling PDF.');
        }

        $xpath = new DOMXPath($dom);
        $pageNodes = $xpath->query('//*[local-name()="page"]');
        $pages = [];

        foreach ($pageNodes as $pageIndex => $pageNode) {
            if (!$pageNode instanceof DOMElement) {
                continue;
            }

            $words = [];
            $wordNodes = $xpath->query('.//*[local-name()="word"]', $pageNode);

            foreach ($wordNodes as $wordNode) {
                if (!$wordNode instanceof DOMElement) {
                    continue;
                }

                $text = $this->cleanText($wordNode->textContent);
                if ($text === '') {
                    continue;
                }

                $words[] = [
                    'text' => $text,
                    'xMin' => (float) $wordNode->getAttribute('xMin'),
                    'xMax' => (float) $wordNode->getAttribute('xMax'),
                    'yMin' => (float) $wordNode->getAttribute('yMin'),
                    'yMax' => (float) $wordNode->getAttribute('yMax'),
                ];
            }

            usort($words, function (array $left, array $right): int {
                if (abs($left['yMin'] - $right['yMin']) <= 1.5) {
                    return $left['xMin'] <=> $right['xMin'];
                }

                return $left['yMin'] <=> $right['yMin'];
            });

            $pages[] = [
                'width' => (float) $pageNode->getAttribute('width'),
                'height' => (float) $pageNode->getAttribute('height'),
                'words' => $words,
                'rectangles' => $this->readPageRectangles($filePath, $pageIndex + 1),
            ];
        }

        return $pages;
    }

    private function readPageRectangles(string $filePath, int $pageNumber): array
    {
        $process = new Process([
            'pdftocairo',
            '-f',
            (string) $pageNumber,
            '-l',
            (string) $pageNumber,
            '-svg',
            $filePath,
            '-',
        ]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $number = '-?\d+(?:\.\d+)?';
        $pattern = "/M\s+({$number})\s+({$number})\s+"
            . "L\s+({$number})\s+({$number})\s+"
            . "L\s+({$number})\s+({$number})\s+"
            . "L\s+({$number})\s+({$number})\s+Z/";
        preg_match_all($pattern, $process->getOutput(), $matches, PREG_SET_ORDER);

        $rectangles = [];
        foreach ($matches as $match) {
            $xValues = [(float) $match[1], (float) $match[3], (float) $match[5], (float) $match[7]];
            $yValues = [(float) $match[2], (float) $match[4], (float) $match[6], (float) $match[8]];
            $xMin = min($xValues);
            $xMax = max($xValues);
            $yMin = min($yValues);
            $yMax = max($yValues);
            $width = $xMax - $xMin;
            $height = $yMax - $yMin;

            if ($width > 5.0 && $height > 8.0 && $height < 160.0) {
                $rectangles[] = compact('xMin', 'xMax', 'yMin', 'yMax', 'width', 'height');
            }
        }

        return $rectangles;
    }

    private function buildLines(array $words): array
    {
        $lines = [];

        foreach ($words as $word) {
            $lastIndex = count($lines) - 1;

            if ($lastIndex < 0 || abs($lines[$lastIndex]['y'] - $word['yMin']) > 1.75) {
                $lines[] = [
                    'y' => $word['yMin'],
                    'yMax' => $word['yMax'],
                    'words' => [$word],
                ];
                continue;
            }

            $lines[$lastIndex]['words'][] = $word;
            $lines[$lastIndex]['yMax'] = max($lines[$lastIndex]['yMax'], $word['yMax']);
        }

        foreach ($lines as &$line) {
            usort($line['words'], function (array $left, array $right): int {
                return $left['xMin'] <=> $right['xMin'];
            });
            $line['text'] = $this->cleanText(implode(' ', array_column($line['words'], 'text')));
        }
        unset($line);

        return $lines;
    }

    private function extractReportDate(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/Report\s+Date:\s*(\d{1,2}-[A-Za-z]{3}-\d{2,4})/i', $line['text'], $match)) {
                return $this->normalizeDate($match[1]);
            }
        }

        return null;
    }

    private function findHeaderBands(
        array $lines,
        array $pageWords,
        float $pageWidth,
        array $rectangles
    ): array
    {
        $bands = [];

        foreach ($lines as $line) {
            if (stripos($line['text'], 'Field Name') === false || stripos($line['text'], 'Well Name') === false) {
                continue;
            }

            $headerWords = array_values(array_filter($pageWords, function (array $word) use ($line): bool {
                $centerY = ($word['yMin'] + $word['yMax']) / 2;

                return $centerY >= $line['y'] - 16.0 && $centerY <= $line['yMax'] + 7.0;
            }));

            $rectangleColumns = $this->columnsFromRectangles(
                ($line['y'] + $line['yMax']) / 2,
                $rectangles,
                $pageWidth
            );

            $bands[] = [
                'start' => max(0.0, $line['y'] - 16.0),
                'end' => $line['yMax'] + 7.0,
                'columns' => $rectangleColumns
                    ?? $this->columnsFromHeaderWords($headerWords, $pageWidth),
            ];
        }

        return $bands;
    }

    private function columnsFromRectangles(float $headerY, array $rectangles, float $pageWidth): ?array
    {
        $groups = [];

        foreach ($rectangles as $rectangle) {
            if ($rectangle['yMin'] > $headerY || $rectangle['yMax'] < $headerY || $rectangle['width'] <= 5.0) {
                continue;
            }

            $key = round($rectangle['yMin'], 1).'|'.round($rectangle['yMax'], 1);
            $groups[$key][] = $rectangle;
        }

        if ($groups === []) {
            return null;
        }

        $candidates = [];
        foreach ($groups as $cells) {
            if (count($cells) !== 12) {
                continue;
            }

            $xMin = min(array_column($cells, 'xMin'));
            $xMax = max(array_column($cells, 'xMax'));
            if ($xMin > 60.0 || $xMax < 780.0) {
                continue;
            }

            $candidates[] = $cells;
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (array $left, array $right): int {
            $leftHeight = max(array_column($left, 'height'));
            $rightHeight = max(array_column($right, 'height'));

            return $rightHeight <=> $leftHeight;
        });
        $cells = $candidates[0];

        usort($cells, function (array $left, array $right): int {
            return $left['xMin'] <=> $right['xMin'];
        });
        $scale = $pageWidth > 0 ? 842.0 / $pageWidth : 1.0;
        $keys = [
            'field_name',
            'well_name',
            'rig_name',
            'objective',
            'spud_date',
            'target_depth',
            'progress',
            'current_depth',
            'budget',
            'cumulative_cost',
            'summary',
        ];
        $columns = [];

        foreach ($keys as $index => $key) {
            $cell = $cells[$index + 1];
            $columns[$key] = [$cell['xMin'] * $scale, $cell['xMax'] * $scale];
        }

        return $columns;
    }

    private function findCompanyMarkers(array $lines): array
    {
        $markers = [];

        foreach ($lines as $line) {
            $firstX = min(array_column($line['words'], 'xMin'));
            $lastX = max(array_column($line['words'], 'xMax'));
            if ($firstX > 150.0) {
                continue;
            }

            $company = $this->normalizeCompanyName($line['text'], $lastX < 350.0);
            if ($company === null) {
                continue;
            }

            $markers[] = [
                'y' => ($line['y'] + $line['yMax']) / 2,
                'company' => $company,
            ];
        }

        usort($markers, function (array $left, array $right): int {
            return $left['y'] <=> $right['y'];
        });

        return $markers;
    }

    private function findRowAnchors(
        array $words,
        array $headerBands,
        float $pageHeight,
        array $rectangles
    ): array
    {
        $anchors = [];

        foreach ($words as $word) {
            $centerY = ($word['yMin'] + $word['yMax']) / 2;

            if (
                $word['xMin'] >= 90.0
                || $centerY >= $pageHeight - 18.0
                || !preg_match('/^\d{1,3}$/', $word['text'])
                || $this->insideAnyBand($centerY, $headerBands)
            ) {
                continue;
            }

            $anchors[] = [
                'number' => (int) $word['text'],
                'y' => $centerY,
                'x' => $word['xMin'],
                'bounds' => $this->findRowBounds($word['xMin'], $centerY, $rectangles),
            ];
        }

        usort($anchors, function (array $left, array $right): int {
            return $left['y'] <=> $right['y'];
        });

        $deduplicated = [];
        foreach ($anchors as $anchor) {
            $lastIndex = count($deduplicated) - 1;
            if ($lastIndex >= 0 && abs($deduplicated[$lastIndex]['y'] - $anchor['y']) <= 2.0) {
                if ($anchor['x'] < $deduplicated[$lastIndex]['x']) {
                    $deduplicated[$lastIndex] = $anchor;
                }
                continue;
            }

            $deduplicated[] = $anchor;
        }

        return $deduplicated;
    }

    private function findRowBounds(float $x, float $y, array $rectangles): ?array
    {
        $candidates = array_values(array_filter($rectangles, function (array $rectangle) use ($x, $y): bool {
            return $rectangle['xMin'] <= $x
                && $rectangle['xMax'] >= $x
                && $rectangle['yMin'] <= $y
                && $rectangle['yMax'] >= $y
                && $rectangle['xMin'] < 100.0
                && $rectangle['xMax'] < 180.0;
        }));

        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (array $left, array $right): int {
            return $right['height'] <=> $left['height'];
        });

        return [
            'top' => $candidates[0]['yMin'],
            'bottom' => $candidates[0]['yMax'],
        ];
    }

    private function columnsFromHeaderWords(array $headerWords, float $pageWidth): array
    {
        $scale = $pageWidth > 0 ? 842.0 / $pageWidth : 1.0;
        $centers = [];
        $wellCenters = [];
        $starts = [];
        $wellStarts = [];

        usort($headerWords, function (array $left, array $right): int {
            return $left['xMin'] <=> $right['xMin'];
        });

        foreach ($headerWords as $word) {
            $text = strtolower($word['text']);
            $center = (($word['xMin'] + $word['xMax']) / 2) * $scale;

            if ($text === 'well') {
                $wellCenters[] = $center;
                $wellStarts[] = $word['xMin'] * $scale;
                continue;
            }

            $labels = [
                'field' => 'field_name',
                'rig' => 'rig_name',
                'spud' => 'spud_date',
                'target' => 'target_depth',
                'progress' => 'progress',
                'current' => 'current_depth',
                'authorized' => 'budget',
                'cumulative' => 'cumulative_cost',
                'summary' => 'summary',
            ];

            if (isset($labels[$text])) {
                $centers[$labels[$text]] = $center;
                $starts[$labels[$text]] = $word['xMin'] * $scale;
            }
        }

        if (isset($wellCenters[0], $wellCenters[1])) {
            $centers['well_name'] = $wellCenters[0];
            $centers['objective'] = $wellCenters[1];
        }

        $required = [
            'field_name', 'well_name', 'rig_name', 'objective', 'spud_date',
            'target_depth', 'progress', 'current_depth', 'budget',
            'cumulative_cost', 'summary',
        ];

        foreach ($required as $key) {
            if (!isset($centers[$key])) {
                return ($centers['spud_date'] ?? 999.0) < 325.0
                    ? self::GROUPED_COLUMNS
                    : self::STANDARD_COLUMNS;
            }
        }

        $fieldWell = $wellStarts[0];
        $wellRig = $starts['rig_name'];
        $rigObjective = $wellStarts[1];
        $objectiveSpud = $starts['spud_date'];
        $spudTarget = ($centers['spud_date'] + $centers['target_depth']) / 2;
        $targetProgress = ($centers['target_depth'] + $centers['progress']) / 2;
        $progressCurrent = ($centers['progress'] + $centers['current_depth']) / 2;
        $currentBudget = ($centers['current_depth'] + $centers['budget']) / 2;
        $budgetCumulative = ($centers['budget'] + $centers['cumulative_cost']) / 2;
        $summaryStart = $centers['cumulative_cost']
            + (($centers['cumulative_cost'] - $centers['budget']) / 2);

        return [
            'field_name' => [0.0, $fieldWell],
            'well_name' => [$fieldWell, $wellRig],
            'rig_name' => [$wellRig, $rigObjective],
            'objective' => [$rigObjective, $objectiveSpud],
            'spud_date' => [$objectiveSpud, $spudTarget],
            'target_depth' => [$spudTarget, $targetProgress],
            'progress' => [$targetProgress, $progressCurrent],
            'current_depth' => [$progressCurrent, $currentBudget],
            'budget' => [$currentBudget, $budgetCumulative],
            'cumulative_cost' => [$budgetCumulative, $summaryStart],
            'summary' => [$summaryStart, 842.0],
        ];
    }

    private function columnsForRow(array $anchor, array $headerBands, array $carriedColumns): array
    {
        if ($anchor['x'] >= 60.0) {
            return self::LEFT_INDENTED_COLUMNS;
        }

        $y = $anchor['y'];
        $columns = $carriedColumns;

        foreach ($headerBands as $band) {
            if ($band['end'] >= $y) {
                break;
            }

            $columns = $band['columns'];
        }

        return $columns;
    }

    private function companyAt(float $y, array $markers, ?string $fallback): ?string
    {
        $company = $fallback;

        foreach ($markers as $marker) {
            if ($marker['y'] >= $y) {
                break;
            }

            $company = $marker['company'];
        }

        return $company;
    }

    private function rowUpperBoundary(int $index, array $anchors, array $markers, array $headerBands): float
    {
        if ($anchors[$index]['bounds'] !== null) {
            return $anchors[$index]['bounds']['top'] - 0.5;
        }

        $y = $anchors[$index]['y'];
        $upper = 0.0;

        if ($index > 0) {
            $upper = ($anchors[$index - 1]['y'] + $y) / 2;
        }

        foreach ($markers as $marker) {
            if ($marker['y'] < $y) {
                $upper = max($upper, $marker['y'] + 3.0);
            }
        }

        foreach ($headerBands as $band) {
            if ($band['end'] < $y) {
                $upper = max($upper, $band['end']);
            }
        }

        return $upper;
    }

    private function rowLowerBoundary(
        int $index,
        array $anchors,
        array $markers,
        array $headerBands,
        float $pageHeight
    ): float {
        if ($anchors[$index]['bounds'] !== null) {
            return $anchors[$index]['bounds']['bottom'] + 0.5;
        }

        $y = $anchors[$index]['y'];
        $lower = $pageHeight - 18.0;

        if (isset($anchors[$index + 1])) {
            $lower = ($y + $anchors[$index + 1]['y']) / 2;
        }

        foreach ($markers as $marker) {
            if ($marker['y'] > $y) {
                $lower = min($lower, $marker['y'] - 3.0);
                break;
            }
        }

        foreach ($headerBands as $band) {
            if ($band['start'] > $y) {
                $lower = min($lower, $band['start']);
                break;
            }
        }

        return $lower;
    }

    private function extractRow(
        array $words,
        array $columns,
        float $upper,
        float $lower,
        array $headerBands,
        array $ignoredBands,
        array $companyMarkers,
        float $pageWidth,
        array $anchor
    ): array {
        $values = array_fill_keys(array_keys($columns), []);
        $scale = $pageWidth > 0 ? 842.0 / $pageWidth : 1.0;

        foreach ($words as $word) {
            $centerY = ($word['yMin'] + $word['yMax']) / 2;
            if (
                $centerY <= $upper
                || $centerY >= $lower
                || $this->insideAnyBand($centerY, $headerBands)
                || $this->insideAnyBand($centerY, $ignoredBands)
            ) {
                continue;
            }

            if ($this->isCompanyMarkerY($centerY, $companyMarkers)) {
                continue;
            }

            if (
                abs($centerY - $anchor['y']) <= 1.75
                && abs($word['xMin'] - $anchor['x']) <= 1.0
                && $word['text'] === (string) $anchor['number']
            ) {
                continue;
            }

            $centerX = (($word['xMin'] + $word['xMax']) / 2) * $scale;

            foreach ($columns as $key => $bounds) {
                if ($centerX >= $bounds[0] && $centerX < $bounds[1]) {
                    $values[$key][] = $word;
                    break;
                }
            }
        }

        foreach ($values as $key => $columnWords) {
            $values[$key] = $this->wordsToText($columnWords);
        }

        return [
            'field_name' => $this->nullableText($values['field_name']),
            'well_name' => $this->nullableText($values['well_name']),
            'rig_name' => $this->nullableText($values['rig_name']),
            'objective' => $this->nullableText($values['objective']),
            'spud_date' => $this->normalizeOptionalDate($values['spud_date']),
            'target_depth' => $this->numericValue($values['target_depth']),
            'progress' => $this->numericValue($values['progress']),
            'current_depth' => $this->numericValue($values['current_depth']),
            'budget' => $this->numericValue($values['budget']),
            'cumulative_cost' => $this->numericValue($values['cumulative_cost']),
            'summary' => $this->nullableText($values['summary']),
        ];
    }

    private function wordsToText(array $words): string
    {
        usort($words, function (array $left, array $right): int {
            if (abs($left['yMin'] - $right['yMin']) <= 1.75) {
                return $left['xMin'] <=> $right['xMin'];
            }

            return $left['yMin'] <=> $right['yMin'];
        });

        $lines = [];
        foreach ($words as $word) {
            $lastIndex = count($lines) - 1;
            if ($lastIndex < 0 || abs($lines[$lastIndex]['y'] - $word['yMin']) > 1.75) {
                $lines[] = ['y' => $word['yMin'], 'texts' => [$word['text']]];
            } else {
                $lines[$lastIndex]['texts'][] = $word['text'];
            }
        }

        $text = implode(' ', array_map(function (array $line): string {
            return implode(' ', $line['texts']);
        }, $lines));

        return $this->cleanText($text);
    }

    private function normalizeCompanyName(string $value, bool $allowUnmapped = true): ?string
    {
        $sourceName = trim((string) preg_replace('/^\s*\d+\s+/', '', $value));
        $key = strtoupper(trim((string) preg_replace('/[^A-Za-z0-9]+/', ' ', $sourceName)));
        $key = preg_replace('/\s+/', ' ', $key);

        $companies = [
            'AKAKUS OIL OPERATIONS' => 'AKAKUS Oil Operations',
            'ARABIAN GULF OIL COMPANY' => 'AGOCO',
            'HAROUGE OIL OPERATIONS' => 'HOG',
            'HAROUGE OIL COMPANY' => 'HOG',
            'SARIR OIL OPERATIONS' => 'SOO',
            'SARIR OIL OPERATION' => 'SOO',
            'SARIR OIL OPERATION B V' => 'SOO',
            'SIRTE OIL COMPANY' => 'Sirte Oil Company',
            'WAHA OIL COMPANY' => 'Waha Oil Company',
            'ZUEITINA OIL COMPANY' => 'ZOC',
            'MELLITAH OIL AND GAS' => 'MOG',
            'MELLITAH OIL GAS' => 'MOG',
            'REPSOL EXP MURZUQ S A' => 'Repsol Exp. Murzuq S.A',
            'ENI NORTH AFRICA BP' => 'ENI North Africa & BP',
            'ENI NORTH AFRICA' => 'ENI North Africa',
            'SIPEX SONATRACH' => 'SIPEX (Sonatrach)',
            'ARKENU' => 'ARKENU',
        ];

        if (isset($companies[$key])) {
            return $companies[$key];
        }

        if (
            $allowUnmapped
            && mb_strlen($sourceName) <= 100
            && preg_match('/\b(?:OIL|GAS)\b/i', $sourceName)
        ) {
            return $this->cleanText($sourceName);
        }

        return null;
    }

    private function findIgnoredBands(array $lines): array
    {
        $bands = [];

        foreach ($lines as $line) {
            if (
                preg_match('/Report\s+(?:Number|Date):/i', $line['text'])
                || preg_match('/^\d+\s*\/\s*\d+$/', $line['text'])
                || in_array(strtolower($line['text']), [
                    'national oil corporation',
                    'drilling and workover department',
                    'daily drilling report',
                ], true)
            ) {
                $bands[] = [
                    'start' => $line['y'] - 1.0,
                    'end' => $line['yMax'] + 1.0,
                ];
            }
        }

        return $bands;
    }

    private function normalizeOptionalDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'TBD') === 0 || $value === '-') {
            return null;
        }

        try {
            return $this->normalizeDate($value);
        } catch (RuntimeException $exception) {
            return null;
        }
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^(\d{1,2})\s*[-.]?\s*([A-Za-z]{3})\s*[-.]?\s*(\d{2,4})$/', $value, $match)) {
            $yearFormat = strlen($match[3]) === 2 ? 'y' : 'Y';

            return Carbon::createFromFormat(
                "j-M-{$yearFormat}",
                "{$match[1]}-{$match[2]}-{$match[3]}"
            )->format('m/d/Y');
        }

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            $format = 'm/d/Y';
        } else {
            throw new RuntimeException("Unsupported NOC report date: {$value}");
        }

        return Carbon::createFromFormat($format, $value)->format('m/d/Y');
    }

    private function numericValue(string $value)
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }

        $normalized = str_replace([',', ' '], '', $value);
        if (!preg_match('/-?\d+(?:\.\d+)?/', $normalized, $match)) {
            return null;
        }

        $number = (float) $match[0];

        return floor($number) === $number ? (int) $number : $number;
    }

    private function nullableText(string $value): ?string
    {
        $value = trim($value);

        return $value === '' || $value === '-' ? null : $value;
    }

    private function cleanText(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value));
        $value = preg_replace('/-\s+/', '-', (string) $value);
        $value = preg_replace('/\s+([,.;:%])/u', '$1', (string) $value);

        return trim((string) $value);
    }

    private function insideAnyBand(float $y, array $bands): bool
    {
        foreach ($bands as $band) {
            if ($y >= $band['start'] && $y <= $band['end']) {
                return true;
            }
        }

        return false;
    }

    private function isCompanyMarkerY(float $y, array $markers): bool
    {
        foreach ($markers as $marker) {
            if (abs($marker['y'] - $y) <= 3.0) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Services\Pdf;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\Process\Process;

class NOCWellExtractorWorkover
{
    private const REPORT_DATE_MODE = 'first_header'; // Options: first_header, page_header

    private const STANDARD_COLUMNS = [
        'field_name' => [47.0, 111.0],
        'well_name' => [111.0, 173.0],
        'rig_name' => [173.0, 233.0],
        'objective' => [233.0, 352.0],
        'start_operation' => [352.0, 399.0],
        'budget' => [399.0, 456.0],
        'cumulative_cost' => [456.0, 532.0],
        'summary' => [532.0, 842.0],
    ];

    public function extract(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('NOC workover PDF not found: '.$filePath);
        }

        $pages = $this->readPositionedPages($filePath);
        $documentReportDate = null;
        $records = [];
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
            throw new RuntimeException('Report Date was not found inside the NOC workover PDF.');
        }

        foreach ($pages as $page) {
            $effectiveReportDate = self::REPORT_DATE_MODE === 'page_header'
                ? ($page['report_date'] ?? $documentReportDate)
                : $documentReportDate;
            $generalRemarksY = $this->findGeneralRemarksY($page['lines']);
            $headerBands = $this->findHeaderBands(
                $page['lines'],
                $page['words'],
                $page['width'],
                $page['rectangles']
            );
            $ignoredBands = $this->findIgnoredBands($page['lines']);
            $companyMarkers = $this->findCompanyMarkers($page['lines'], $generalRemarksY);
            $rowAnchors = $this->findRowAnchors(
                $page['words'],
                $headerBands,
                $page['height'],
                $page['rectangles'],
                $generalRemarksY
            );

            foreach ($rowAnchors as $index => $anchor) {
                $company = $this->companyAt($anchor['y'], $companyMarkers, $currentCompany);
                if ($company === null) {
                    continue;
                }

                $record = $this->extractRow(
                    $page['words'],
                    $this->columnsForRow($anchor, $headerBands, $currentColumns),
                    $this->rowUpperBoundary($index, $rowAnchors, $companyMarkers, $headerBands),
                    $this->rowLowerBoundary(
                        $index,
                        $rowAnchors,
                        $companyMarkers,
                        $headerBands,
                        $page['height'],
                        $generalRemarksY
                    ),
                    $headerBands,
                    $ignoredBands,
                    $companyMarkers,
                    $page['width'],
                    $anchor
                );

                if ($record['well_name'] === null) {
                    continue;
                }

                $records[] = $this->withReportMetadata($record, $company, $effectiveReportDate);
            }

            foreach ($this->extractGeneralRemarkRecords($page['lines']) as $record) {
                $company = $record['company_name'];
                unset($record['company_name']);
                $records[] = $this->withReportMetadata($record, $company, $effectiveReportDate);
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

    private function withReportMetadata(array $record, string $company, string $reportDate): array
    {
        $record['company_name'] = $company;
        $record['report_date'] = $reportDate;
        $record['report_no'] = $this->systemReportNumber($reportDate);

        return $record;
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
            throw new RuntimeException('Unable to parse positioned text from the NOC workover PDF.');
        }

        $xpath = new DOMXPath($dom);
        $pages = [];
        foreach ($xpath->query('//*[local-name()="page"]') as $pageIndex => $pageNode) {
            if (!$pageNode instanceof DOMElement) {
                continue;
            }

            $words = [];
            foreach ($xpath->query('.//*[local-name()="word"]', $pageNode) as $wordNode) {
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
            usort($words, fn (array $a, array $b): int => abs($a['yMin'] - $b['yMin']) <= 1.5
                ? $a['xMin'] <=> $b['xMin']
                : $a['yMin'] <=> $b['yMin']);

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
            'pdftocairo', '-f', (string) $pageNumber, '-l', (string) $pageNumber,
            '-svg', $filePath, '-',
        ]);
        $process->setTimeout(120);
        $process->run();
        if (!$process->isSuccessful()) {
            return [];
        }

        $number = '-?\d+(?:\.\d+)?';
        $pattern = "/M\s+({$number})\s+({$number})\s+"
            ."L\s+({$number})\s+({$number})\s+"
            ."L\s+({$number})\s+({$number})\s+"
            ."L\s+({$number})\s+({$number})\s+Z/";
        preg_match_all($pattern, $process->getOutput(), $matches, PREG_SET_ORDER);

        $rectangles = [];
        foreach ($matches as $match) {
            $xs = [(float) $match[1], (float) $match[3], (float) $match[5], (float) $match[7]];
            $ys = [(float) $match[2], (float) $match[4], (float) $match[6], (float) $match[8]];
            $xMin = min($xs);
            $xMax = max($xs);
            $yMin = min($ys);
            $yMax = max($ys);
            $width = $xMax - $xMin;
            $height = $yMax - $yMin;
            if ($width > 5.0 && $height > 8.0 && $height < 180.0) {
                $rectangles[] = compact('xMin', 'xMax', 'yMin', 'yMax', 'width', 'height');
            }
        }

        return $rectangles;
    }

    private function buildLines(array $words): array
    {
        $lines = [];
        foreach ($words as $word) {
            $last = count($lines) - 1;
            if ($last < 0 || abs($lines[$last]['y'] - $word['yMin']) > 1.75) {
                $lines[] = ['y' => $word['yMin'], 'yMax' => $word['yMax'], 'words' => [$word]];
            } else {
                $lines[$last]['words'][] = $word;
                $lines[$last]['yMax'] = max($lines[$last]['yMax'], $word['yMax']);
            }
        }
        foreach ($lines as &$line) {
            usort($line['words'], fn (array $a, array $b): int => $a['xMin'] <=> $b['xMin']);
            $line['text'] = $this->cleanText(implode(' ', array_column($line['words'], 'text')));
        }
        unset($line);

        return $lines;
    }

    private function extractReportDate(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/Report\s+Date:\s*(\d{1,2}\s*[-.]?\s*[A-Za-z]{3}\s*[-.]?\s*\d{2,4})/i', $line['text'], $match)) {
                return $this->normalizeDate($match[1]);
            }
        }

        return null;
    }

    private function findGeneralRemarksY(array $lines): ?float
    {
        foreach ($lines as $line) {
            if (stripos($line['text'], 'General Remarks') !== false) {
                return ($line['y'] + $line['yMax']) / 2;
            }
        }

        return null;
    }

    private function findHeaderBands(array $lines, array $words, float $pageWidth, array $rectangles): array
    {
        $bands = [];
        foreach ($lines as $line) {
            if (stripos($line['text'], 'Field Name') === false || stripos($line['text'], 'Well Name') === false) {
                continue;
            }
            $headerWords = array_values(array_filter($words, function (array $word) use ($line): bool {
                $y = ($word['yMin'] + $word['yMax']) / 2;

                return $y >= $line['y'] - 16.0 && $y <= $line['yMax'] + 7.0;
            }));
            $bands[] = [
                'start' => max(0.0, $line['y'] - 16.0),
                'end' => $line['yMax'] + 7.0,
                'columns' => $this->columnsFromRectangles(
                    ($line['y'] + $line['yMax']) / 2,
                    $rectangles,
                    $pageWidth
                ) ?? $this->columnsFromHeaderWords($headerWords, $pageWidth),
            ];
        }

        return $bands;
    }

    private function columnsFromRectangles(float $headerY, array $rectangles, float $pageWidth): ?array
    {
        $groups = [];
        foreach ($rectangles as $rectangle) {
            if ($rectangle['yMin'] <= $headerY && $rectangle['yMax'] >= $headerY) {
                $groups[round($rectangle['yMin'], 1).'|'.round($rectangle['yMax'], 1)][] = $rectangle;
            }
        }

        $candidates = [];
        foreach ($groups as $cells) {
            if (count($cells) !== 9) {
                continue;
            }
            if (min(array_column($cells, 'xMin')) <= 60.0 && max(array_column($cells, 'xMax')) >= 780.0) {
                $candidates[] = $cells;
            }
        }
        if ($candidates === []) {
            return null;
        }
        usort($candidates, fn (array $a, array $b): int =>
            max(array_column($b, 'height')) <=> max(array_column($a, 'height')));
        $cells = $candidates[0];
        usort($cells, fn (array $a, array $b): int => $a['xMin'] <=> $b['xMin']);

        $scale = $pageWidth > 0 ? 842.0 / $pageWidth : 1.0;
        $keys = ['field_name', 'well_name', 'rig_name', 'objective', 'start_operation', 'budget', 'cumulative_cost', 'summary'];
        $columns = [];
        foreach ($keys as $index => $key) {
            $columns[$key] = [$cells[$index + 1]['xMin'] * $scale, $cells[$index + 1]['xMax'] * $scale];
        }

        return $columns;
    }

    private function columnsFromHeaderWords(array $words, float $pageWidth): array
    {
        // Header labels are centered inside cells, so their text positions are not
        // reliable column boundaries. Exact SVG cell rectangles are preferred;
        // this fixed template geometry is the safe fallback for legacy PDFs.
        return self::STANDARD_COLUMNS;
    }

    private function findCompanyMarkers(array $lines, ?float $generalRemarksY): array
    {
        $markers = [];
        foreach ($lines as $line) {
            $y = ($line['y'] + $line['yMax']) / 2;
            if ($generalRemarksY !== null && $y >= $generalRemarksY) {
                continue;
            }
            $firstX = min(array_column($line['words'], 'xMin'));
            $lastX = max(array_column($line['words'], 'xMax'));
            if ($firstX > 150.0) {
                continue;
            }
            $company = $this->normalizeCompanyName($line['text'], $lastX < 380.0);
            if ($company !== null) {
                $markers[] = ['y' => $y, 'company' => $company];
            }
        }
        usort($markers, fn (array $a, array $b): int => $a['y'] <=> $b['y']);

        return $markers;
    }

    private function findRowAnchors(
        array $words,
        array $headerBands,
        float $pageHeight,
        array $rectangles,
        ?float $generalRemarksY
    ): array {
        $anchors = [];
        foreach ($words as $word) {
            $y = ($word['yMin'] + $word['yMax']) / 2;
            if (
                $word['xMin'] >= 90.0
                || $y >= $pageHeight - 18.0
                || ($generalRemarksY !== null && $y >= $generalRemarksY)
                || !preg_match('/^\d{1,3}$/', $word['text'])
                || $this->insideAnyBand($y, $headerBands)
            ) {
                continue;
            }
            $anchors[] = [
                'number' => (int) $word['text'],
                'y' => $y,
                'x' => $word['xMin'],
                'bounds' => $this->findRowBounds($word['xMin'], $y, $rectangles),
            ];
        }
        usort($anchors, fn (array $a, array $b): int => $a['y'] <=> $b['y']);
        $result = [];
        foreach ($anchors as $anchor) {
            $last = count($result) - 1;
            if ($last >= 0 && abs($result[$last]['y'] - $anchor['y']) <= 2.0) {
                if ($anchor['x'] < $result[$last]['x']) {
                    $result[$last] = $anchor;
                }
            } else {
                $result[] = $anchor;
            }
        }

        return $result;
    }

    private function findRowBounds(float $x, float $y, array $rectangles): ?array
    {
        $matches = array_values(array_filter($rectangles, fn (array $r): bool =>
            $r['xMin'] <= $x && $r['xMax'] >= $x
            && $r['yMin'] <= $y && $r['yMax'] >= $y
            && $r['xMin'] < 100.0 && $r['xMax'] < 180.0));
        if ($matches === []) {
            return null;
        }
        usort($matches, fn (array $a, array $b): int => $b['height'] <=> $a['height']);

        return ['top' => $matches[0]['yMin'], 'bottom' => $matches[0]['yMax']];
    }

    private function columnsForRow(array $anchor, array $bands, array $carried): array
    {
        $columns = $carried;
        foreach ($bands as $band) {
            if ($band['end'] >= $anchor['y']) {
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

    private function rowUpperBoundary(int $index, array $anchors, array $markers, array $bands): float
    {
        if ($anchors[$index]['bounds'] !== null) {
            return $anchors[$index]['bounds']['top'] - 0.5;
        }
        $y = $anchors[$index]['y'];
        $upper = $index > 0 ? ($anchors[$index - 1]['y'] + $y) / 2 : 0.0;
        foreach ($markers as $marker) {
            if ($marker['y'] < $y) {
                $upper = max($upper, $marker['y'] + 3.0);
            }
        }
        foreach ($bands as $band) {
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
        array $bands,
        float $pageHeight,
        ?float $generalRemarksY
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
        foreach ($bands as $band) {
            if ($band['start'] > $y) {
                $lower = min($lower, $band['start']);
                break;
            }
        }
        if ($generalRemarksY !== null && $generalRemarksY > $y) {
            $lower = min($lower, $generalRemarksY - 2.0);
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
            $y = ($word['yMin'] + $word['yMax']) / 2;
            if (
                $y <= $upper || $y >= $lower
                || $this->insideAnyBand($y, $headerBands)
                || $this->insideAnyBand($y, $ignoredBands)
                || $this->isCompanyMarkerY($y, $companyMarkers)
            ) {
                continue;
            }
            if (
                abs($y - $anchor['y']) <= 1.75
                && abs($word['xMin'] - $anchor['x']) <= 1.0
                && $word['text'] === (string) $anchor['number']
            ) {
                continue;
            }
            $x = (($word['xMin'] + $word['xMax']) / 2) * $scale;
            foreach ($columns as $key => $bounds) {
                if ($x >= $bounds[0] && $x < $bounds[1]) {
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
            'start_operation' => $this->normalizeOptionalDate($values['start_operation']),
            'budget' => $this->numericValue($values['budget']),
            'cumulative_cost' => $this->numericValue($values['cumulative_cost']),
            'summary' => $this->nullableText($values['summary']),
            'days' => null,
        ];
    }

    private function extractGeneralRemarkRecords(array $lines): array
    {
        $inRemarks = false;
        $entries = [];
        $current = null;
        $currentCompany = null;

        foreach ($lines as $line) {
            $text = $line['text'];
            if (!$inRemarks && stripos($text, 'General Remarks') !== false) {
                $inRemarks = true;
                continue;
            }

            $company = $this->normalizeCompanyName($text, false);
            if ($company !== null && ($inRemarks || in_array($company, ['HOG', 'MOG'], true))) {
                if ($current !== null && $currentCompany !== null) {
                    $entries[] = ['company' => $currentCompany, 'text' => $current];
                    $current = null;
                }
                $inRemarks = true;
                $currentCompany = $company;
                continue;
            }
            if (!$inRemarks) {
                continue;
            }
            if ($currentCompany === null) {
                continue;
            }
            if ($this->isReportFurniture($text)) {
                continue;
            }
            if (preg_match('/^Prepared\s+By:/i', $text)) {
                break;
            }
            if (preg_match('/^\s*(\d{1,2})\s*[\.\-]\s*(.+)$/u', $text, $match)) {
                if ($current !== null) {
                    $entries[] = ['company' => $currentCompany, 'text' => $current];
                }
                $current = trim($match[2]);
            } elseif ($current !== null && !preg_match('/^\d+\s*\/\s*\d+$/', $text)) {
                $current .= ' '.$text;
            }
        }
        if ($current !== null && $currentCompany !== null) {
            $entries[] = ['company' => $currentCompany, 'text' => $current];
        }

        $records = [];
        foreach ($entries as $entry) {
            $record = $this->parseGeneralRemarkEntry($entry['text'], $entry['company']);
            if ($record !== null) {
                $record['company_name'] = $entry['company'];
                $records[] = $record;
            }
        }

        return $records;
    }

    private function parseGeneralRemarkEntry(string $entry, string $company): ?array
    {
        $parts = array_map(fn (string $part): string => $this->cleanText($part), explode('/', $entry, 4));
        if (count($parts) < 3) {
            return null;
        }

        $field = null;
        $well = null;
        $rig = null;
        $tail = null;
        if ($company === 'MOG' && preg_match('/^(?:NWD\s*\d+|RIGLESS)$/i', $parts[1] ?? '')) {
            $field = $parts[0];
            $rig = $parts[1];
            $well = $parts[2] ?? null;
            $tail = $parts[3] ?? null;
            if ($tail !== null && preg_match('/^([A-Za-z]?\d+)\s*\/\s*(.+)$/u', $tail, $match)) {
                $well .= '/'.$match[1];
                $tail = $match[2];
            }
        } elseif ($company === 'MOG' && count($parts) === 3) {
            $field = $parts[0];
            $well = $parts[1];
            $tail = $parts[2];
        } elseif (preg_match('/^(AMAL|GHANI|EN[\s-]?NAGA)\s+(.+)$/i', $parts[0], $match)) {
            $field = $match[1];
            $well = $match[2];
            $rig = $parts[1] ?? null;
            $tail = implode(' / ', array_slice($parts, 2));
        } else {
            $field = $parts[0] ?? null;
            $well = $parts[1] ?? null;
            $rig = $parts[2] ?? null;
            $tail = $parts[3] ?? null;
            if (
                $company === 'HOG'
                && preg_match('/^(.*?)[\'’]\s*(.+)$/i', (string) $rig, $match)
            ) {
                $rig = trim($match[1]);
                $tail = trim($match[2]).($tail !== null ? ' / '.$tail : '');
            }
        }

        if ($well === null || trim($well) === '') {
            return null;
        }

        $cumulativeCost = null;
        if ($tail !== null && preg_match('/CUM\.?\s*COST\s*:?\s*\$?\s*([\d,\.\s]+)/i', $tail, $match)) {
            $cumulativeCost = $this->numericValue($match[1]);
            $tail = preg_replace('/CUM\.?\s*COST\s*:?\s*\$?\s*[\d,\.\s]+[\/:\-]?\s*/i', '', $tail, 1);
        }

        return [
            'field_name' => $this->nullableText((string) $field),
            'well_name' => $this->nullableText((string) $well),
            'rig_name' => $this->nullableText((string) $rig),
            'objective' => null,
            'start_operation' => null,
            'budget' => null,
            'cumulative_cost' => $cumulativeCost,
            'summary' => $this->nullableText((string) $tail),
            'days' => null,
        ];
    }

    private function normalizeCompanyName(string $value, bool $allowUnmapped = true): ?string
    {
        $source = trim((string) preg_replace('/^\s*\d+\s+/', '', $value));
        $key = strtoupper(trim((string) preg_replace('/[^A-Za-z0-9]+/', ' ', $source)));
        $key = preg_replace('/\s+/', ' ', $key);
        $companies = [
            'AKAKUS OIL OPERATIONS' => 'AKAKUS Oil Operations',
            'ARABIAN GULF OIL COMPANY' => 'AGOCO',
            'HAROUGE OIL OPERATIONS' => 'HOG',
            'HAROUGE OIL COMPANY' => 'HOG',
            'HOO' => 'HOG',
            'SARIR OIL OPERATIONS' => 'SOO',
            'SARIR OIL OPERATION' => 'SOO',
            'SARIR OIL OPERATION B V' => 'SOO',
            'SIRTE OIL COMPANY' => 'Sirte Oil Company',
            'WAHA OIL COMPANY' => 'WAHA Oil Company',
            'ZUEITINA OIL COMPANY' => 'ZOC',
            'MELLITAH OIL AND GAS' => 'MOG',
            'MELLITAH OIL GAS' => 'MOG',
            'MELLITAH OIL GAS B V LIBYAN BRANCH' => 'MOG',
            'MELLITAH OIL GAS COMPANY' => 'MOG',
            'AKAKUS OIL' => 'AKAKUS Oil Operations',
            'ARABIAN GULF OIL' => 'AGOCO',
            'ZUEITINA OIL' => 'ZOC',
        ];
        if (isset($companies[$key])) {
            return $companies[$key];
        }
        if ($allowUnmapped && mb_strlen($source) <= 100 && preg_match('/\b(?:OIL|GAS)\b/i', $source)) {
            return $this->cleanText($source);
        }

        return null;
    }

    private function findIgnoredBands(array $lines): array
    {
        $bands = [];
        foreach ($lines as $line) {
            if ($this->isReportFurniture($line['text'])) {
                $bands[] = ['start' => $line['y'] - 1.0, 'end' => $line['yMax'] + 1.0];
            }
        }

        return $bands;
    }

    private function isReportFurniture(string $text): bool
    {
        return (bool) (
            preg_match('/Report\s+(?:Number|Date):/i', $text)
            || preg_match('/^\d+\s*\/\s*\d+$/', $text)
            || in_array(strtolower($text), [
                'national oil corporation',
                'drilling and workover department',
                'daily workover report',
            ], true)
        );
    }

    private function wordsToText(array $words): string
    {
        usort($words, fn (array $a, array $b): int => abs($a['yMin'] - $b['yMin']) <= 1.75
            ? $a['xMin'] <=> $b['xMin']
            : $a['yMin'] <=> $b['yMin']);
        $lines = [];
        foreach ($words as $word) {
            $last = count($lines) - 1;
            if ($last < 0 || abs($lines[$last]['y'] - $word['yMin']) > 1.75) {
                $lines[] = ['y' => $word['yMin'], 'texts' => [$word['text']]];
            } else {
                $lines[$last]['texts'][] = $word['text'];
            }
        }

        return $this->cleanText(implode(' ', array_map(
            fn (array $line): string => implode(' ', $line['texts']),
            $lines
        )));
    }

    private function normalizeOptionalDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || strcasecmp($value, 'TBD') === 0) {
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
            $format = strlen($match[3]) === 2 ? 'y' : 'Y';

            return Carbon::createFromFormat('j-M-'.$format, $match[1].'-'.$match[2].'-'.$match[3])->format('m/d/Y');
        }
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            return Carbon::createFromFormat('m/d/Y', $value)->format('m/d/Y');
        }

        throw new RuntimeException('Unsupported NOC workover date: '.$value);
    }

    private function systemReportNumber(string $reportDate): int
    {
        $start = Carbon::createFromFormat('m/d/Y', '01/01/2026')->startOfDay();
        $date = Carbon::createFromFormat('m/d/Y', $reportDate)->startOfDay();

        return 366 + (int) $start->diffInDays($date, false);
    }

    private function numericValue(string $value)
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }
        $normalized = str_replace([',', ' ', '$'], '', $value);
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

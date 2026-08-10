<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class AOOWellExtractorWorkover
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): array
    {
        Log::debug('[AOO Workover] Extractor started', ['file' => $filePath]);

        $pdf = $this->parser->parseFile($filePath);
        $lines = [];
        $pageTexts = [];

        foreach ($pdf->getPages() as $page) {
            $rawText = $page->getText();
            $pageTexts[] = $rawText;
            $text = str_replace(["\r\n", "\r"], "\n", $rawText);

            foreach (explode("\n", $text) as $line) {
                $line = trim($line);

                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $results = $this->parseLines($lines);

        // Preserve the established parser for normal reports. Only recover when
        // it found no wells and the PDF has the known private-use font signature.
        if ($results === [] && $this->containsPrivateUseCharacters(implode("\n", $pageTexts))) {
            Log::debug('[AOO Workover] Private-use font encoding detected; using recovery parser');
            $results = $this->parseLines($this->normalizeDamagedPages($pageTexts));
        }

        Log::debug('[AOO Workover] Extractor finished', ['rows' => count($results)]);

        return $results;
    }

    protected function parseLines(array $lines): array
    {
        $results = [];
        $currentField = null;
        $recordField = null;
        $header = null;
        $block = [];

        foreach ($lines as $line) {
            if ($this->isFieldCodeLine($line)) {
                $currentField = strtoupper($line);
                continue;
            }

            if ($this->isWellHeaderLine($line)) {
                if ($header !== null) {
                    $results[] = $this->parseRecord($header, $block, $recordField);
                }

                $header = $line;
                $block = [];
                $recordField = $currentField;
                continue;
            }

            if ($header !== null) {
                $block[] = $line;
            }
        }

        if ($header !== null) {
            $results[] = $this->parseRecord($header, $block, $recordField);
        }

        return $results;
    }

    protected function parseRecord(string $header, array $lines, ?string $fieldName): array
    {
        $record = [
            'well_name' => null,
            'field_name' => $fieldName,
            'rig_name' => null,
            'objective' => null,
            'budget' => null,
            'cumulative_cost' => null,
            'summary' => null,
            'report_no' => null,
        ];

        if (preg_match('/Well\s+Name:\s*(.*?)(?:DATE\s*:|\s+Rig\s+Name\s*\/\s*NO\.?\s*:)/i', $header, $match)) {
            $record['well_name'] = trim($match[1]) ?: null;
        }

        if (preg_match('/Rig\s+Name\s*\/\s*NO\.?\s*:\s*(.*?)\s+Event\s+Code\s*:/i', $header, $match)) {
            $record['rig_name'] = trim($match[1]) ?: null;
        }

        // Prefer the well-name suffix over page state when it is available.
        if (preg_match('/-(NC\d+)\s*$/i', (string) $record['well_name'], $match)) {
            $record['field_name'] = strtoupper($match[1]);
        } elseif (stripos((string) $record['well_name'], 'I&R') !== false) {
            $record['field_name'] = 'I&R';
        }

        foreach ($lines as $line) {
            if ($record['objective'] === null) {
                if (preg_match('/^(.+?)OBJECTIVE\s*:/i', $line, $match)) {
                    $record['objective'] = $this->cleanText($match[1]);
                } elseif (preg_match('/OBJECTIVE\s*:\s*(.+?)(?=\s+Planned\s+Workover\s+Days\s*:|$)/i', $line, $match)) {
                    $record['objective'] = $this->cleanText($match[1]);
                }
            }

            if ($record['report_no'] === null && preg_match('/REPORT\s+No\s*:\s*(\d+)/i', $line, $match)) {
                $record['report_no'] = (int) $match[1];
            }

            if ($record['budget'] === null && preg_match('/Estimated\s+Workover\s+Cost\s*:\s*([\d,]+(?:\.\d+)?)/i', $line, $match)) {
                $record['budget'] = $this->toNumber($match[1]);
            }

            if (
                ($record['report_no'] === null || $record['budget'] === null)
                && preg_match(
                    '/Estimated\s+Workover\s+Cost\s*:\s*\(\$\)\s*(\d+)\s+([\d,]+(?:\.\d+)?)(?=\s+BWPD\s*:|$)/i',
                    $line,
                    $match
                )
            ) {
                $record['report_no'] = (int) $match[1];
                $record['budget'] = $this->toNumber($match[2]);
            }

            if (
                $record['cumulative_cost'] === null
                && preg_match('/CUM\.\s*COST\s*:\s*(?:\(\$\)\s*)?([\d,]+(?:\.\d+)?)/i', $line, $match)
            ) {
                $record['cumulative_cost'] = $this->toNumber($match[1]);
            }
        }

        if ($record['objective'] === null) {
            $record['objective'] = $this->findSplitObjective($lines);
        }

        if ($record['report_no'] === null) {
            $record['report_no'] = $this->findSplitReportNumber($lines);
        }

        if ($record['budget'] === null) {
            $record['budget'] = $this->findSplitBudget($lines);
        }

        if ($record['cumulative_cost'] === null) {
            $record['cumulative_cost'] = $this->findSplitCumulativeCost($lines);
        }

        $record['summary'] = $this->extractSummary($lines);

        return $record;
    }

    protected function extractSummary(array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            if (!preg_match('/Oper\.?\s*Summary\s*:/i', $line, $marker, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $markerText = $marker[0][0];
            $markerPosition = $marker[0][1];
            $beforeMarker = trim(substr($line, 0, $markerPosition));
            $afterMarker = trim(substr($line, $markerPosition + strlen($markerText)));

            // Standard layout: summary text follows the label.
            if ($afterMarker !== '') {
                $parts = [$afterMarker];

                for ($next = $index + 1; $next < count($lines); $next++) {
                    if ($this->isWellHeaderLine($lines[$next])) {
                        break;
                    }

                    [$content, $stop] = $this->splitAtSummaryStop($lines[$next]);

                    if ($content !== '') {
                        $parts[] = $content;
                    }

                    if ($stop) {
                        break;
                    }
                }

                return $this->cleanText(implode(' ', $parts));
            }

            // Reversed PDF text order: summary text appears immediately before
            // an empty label, sometimes ending on the same line as the label.
            $parts = [];

            if ($beforeMarker !== '') {
                array_unshift($parts, $beforeMarker);
            }

            for ($previous = $index - 1; $previous >= 0; $previous--) {
                $candidate = trim($lines[$previous]);

                if (!$this->isNarrativeSummaryLine($candidate)) {
                    break;
                }

                array_unshift($parts, $candidate);
            }

            return $this->cleanText(implode(' ', $parts));
        }

        return null;
    }

    protected function splitAtSummaryStop(string $line): array
    {
        if (preg_match('/^(.*?)(?:Next\s+Operations?|General\s+Notes)\s*:/i', $line, $match)) {
            return [trim($match[1]), true];
        }

        return [trim($line), false];
    }

    protected function isNarrativeSummaryLine(string $line): bool
    {
        if ($line === '' || $this->isWellHeaderLine($line) || $this->isFieldCodeLine($line)) {
            return false;
        }

        if (
            preg_match(
                '/^(?:OBJECTIVE|REPORT\s+No|RIG\s+SUPERVISOR|WELL\s+TD|PBTD|DOL\s*\/\s*DFS|PROD\.\s*Intervals|TUBING|CASING|CUM\.\s*COST|DAILY\s+COST|Estimated\s+Workover\s+Cost|Planned\s+Workover\s+Days|Last\s+Date\s+Of\s+Services|BOPD|BWPD|WC%|Reservoir|Opening\s+Type)\s*:/i',
                $line
            )
            || preg_match('/^(?:Saturday|Sunday|Monday|Tuesday|Wednesday|Thursday|Friday),/i', $line)
            || strpos($line, '--') !== false
            || preg_match('/^[\d,\.]+\s*(?:\([^)]+\))?$/', $line)
            || preg_match('/^(?:Mamuniyat|Hawaz|Hasawnah)\b/i', $line)
            || (
                preg_match('/^\d/', $line)
                && preg_match('/(?:PERFORATED|OPEN\s+HOLE|Mamuniyat|Hawaz|Hasawnah)/i', $line)
            )
        ) {
            return false;
        }

        return preg_match('/[A-Za-z]/', $line) === 1;
    }

    protected function findSplitObjective(array $lines): ?string
    {
        $start = $this->findLineIndex($lines, '/^OBJECTIVE\s*:\s*$/i');

        if ($start === null) {
            return null;
        }

        for ($index = $start + 1; $index < count($lines); $index++) {
            $candidate = trim($lines[$index]);

            if (preg_match('/^Oper\.?\s*Summary\s*:/i', $candidate)) {
                break;
            }

            if (
                $candidate === ''
                || strpos($candidate, ':') !== false
                || preg_match('/^[\d\s,\.\/\-\(\)\$]+$/', $candidate)
            ) {
                continue;
            }

            return $this->cleanText($candidate);
        }

        return null;
    }

    protected function findSplitReportNumber(array $lines): ?int
    {
        $start = $this->findLineIndex($lines, '/^REPORT\s+No\s*:\s*$/i');

        if ($start === null) {
            return null;
        }

        for ($index = $start + 1; $index < count($lines); $index++) {
            if (preg_match('/^Oper\.?\s*Summary\s*:/i', $lines[$index])) {
                break;
            }

            if (preg_match('/^\d+$/', trim($lines[$index]), $match)) {
                return (int) $match[0];
            }
        }

        return null;
    }

    protected function findSplitBudget(array $lines): ?float
    {
        $start = $this->findLineIndex($lines, '/^Estimated\s+Workover\s+Cost\s*:\s*$/i');

        if ($start === null) {
            return null;
        }

        for ($index = $start + 1; $index < count($lines); $index++) {
            if (preg_match('/^([\d,]+\.\d{2})$/', trim($lines[$index]), $match)) {
                return $this->toNumber($match[1]);
            }

            if (preg_match('/^Oper\.?\s*Summary\s*:/i', $lines[$index])) {
                break;
            }
        }

        return null;
    }

    protected function findSplitCumulativeCost(array $lines): ?float
    {
        $start = $this->findLineIndex($lines, '/^CUM\.\s*COST\s*:\s*$/i');

        if ($start === null) {
            return null;
        }

        $values = [];

        for ($index = $start + 1; $index < count($lines); $index++) {
            if (preg_match('/^Oper\.?\s*Summary\s*:/i', $lines[$index])) {
                break;
            }

            if (preg_match('/^([\d,]+(?:\.\d+)?)\s*\(\$\)$/', trim($lines[$index]), $match)) {
                $values[] = $this->toNumber($match[1]);
            }
        }

        return empty($values) ? null : end($values);
    }

    protected function findLineIndex(array $lines, string $pattern): ?int
    {
        foreach ($lines as $index => $line) {
            if (preg_match($pattern, trim($line))) {
                return $index;
            }
        }

        return null;
    }

    protected function isWellHeaderLine(string $line): bool
    {
        return preg_match(
            '/DATE\s*:.*?Well\s+Name\s*:|Well\s+Name\s*:.*?DATE\s*:/i',
            $line
        ) === 1 && preg_match('/Rig\s+Name\s*\/\s*NO\.?\s*:/i', $line) === 1;
    }

    protected function isFieldCodeLine(string $line): bool
    {
        return preg_match('/^(?:NC\d+|I&R)$/i', $line) === 1;
    }

    private function toNumber(string $value): float
    {
        return (float) str_replace(',', '', $value);
    }

    private function cleanText(string $text): ?string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/,\s*,/', ',', $text);

        // OCR fixes
        $text = str_replace(['C0ONT', 'SAWB'], ['CONT', 'SWAB'], $text);
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    // ------------------------------------------------------------------ //
    //  PRIVATE-USE FONT RECOVERY
    // ------------------------------------------------------------------ //

    protected function containsPrivateUseCharacters(string $text): bool
    {
        return preg_match('/[\x{F000}-\x{F0FF}]/u', $text) === 1;
    }

    protected function decodePrivateUseCharacters(string $text): string
    {
        return preg_replace_callback(
            '/[\x{F000}-\x{F0FF}]/u',
            static function (array $match): string {
                $packed = mb_convert_encoding($match[0], 'UCS-4BE', 'UTF-8');
                $codePoint = unpack('N', $packed)[1] - 0xF000;

                return mb_convert_encoding(pack('N', $codePoint), 'UTF-8', 'UCS-4BE');
            },
            $text
        );
    }

    protected function normalizeDamagedPages(array $pageTexts): array
    {
        $normalized = [];
        $currentField = null;
        $allLines = [];

        // Keep split records intact by segmenting wells only after every decoded
        // page has been flattened into one continuous report stream.
        foreach ($pageTexts as $pageText) {
            foreach ($this->cleanRecoveredLines($this->decodePrivateUseCharacters($pageText)) as $line) {
                $allLines[] = $line;
            }
        }

        $wellIndexes = $this->findRecoveredWellIndexes($allLines);
        $scanStart = 0;

        foreach ($wellIndexes as $position => $start) {
            for ($index = $scanStart; $index < $start; $index++) {
                if ($this->isFieldCodeLine($allLines[$index])) {
                    $currentField = strtoupper($allLines[$index]);
                }
            }

            $end = $wellIndexes[$position + 1] ?? count($allLines);
            $block = array_slice($allLines, $start, $end - $start);

            foreach ($this->normalizeRecoveredRecord($block, $currentField) as $line) {
                $normalized[] = $line;
            }

            $scanStart = $start + 1;
        }

        return $normalized;
    }

    protected function cleanRecoveredLines(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim(preg_replace('/[\t ]+/u', ' ', $line));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    protected function findRecoveredWellIndexes(array $lines): array
    {
        $indexes = [];

        foreach ($lines as $index => $line) {
            if (preg_match('/^Well\s+Name\s*:\s*$/i', $line)) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    protected function normalizeRecoveredRecord(array $block, ?string $fieldName): array
    {
        $wellName = $block[1] ?? null;
        $rigName = $this->recoveredValueAfterLabel($block, 'Rig Name / NO.:');
        $objectiveIndex = $this->recoveredIndexContaining($block, 'OBJECTIVE:');
        $objective = $objectiveIndex !== null && $objectiveIndex > 0
            ? $block[$objectiveIndex - 1]
            : null;
        [$reportNo, $budget] = $this->extractRecoveredReportAndBudget($block);
        $cumulativeCost = $this->extractRecoveredNumberBetween($block, 'CUM. COST:', 'DOL / DFS:');
        $summary = $this->extractRecoveredSummary($block);

        if ($wellName === null || $wellName === '') {
            return [];
        }

        $lines = [];
        if ($fieldName !== null) {
            $lines[] = $fieldName;
        }

        $lines[] = sprintf(
            'DATE: Well Name:%s Rig Name / NO.:%s Event Code:',
            $wellName,
            $rigName ?? ''
        );

        if ($objective !== null) {
            $lines[] = 'OBJECTIVE: ' . $objective;
        }
        if ($reportNo !== null) {
            $lines[] = 'REPORT No:' . $reportNo;
        }
        if ($budget !== null) {
            $lines[] = 'Estimated Workover Cost:' . $budget;
        }
        if ($cumulativeCost !== null) {
            $lines[] = 'CUM. COST:' . $cumulativeCost;
        }

        $lines[] = 'Oper. Summary:' . ($summary ?? '');
        $lines[] = 'Next Operations:';

        return $lines;
    }

    protected function recoveredValueAfterLabel(array $lines, string $label): ?string
    {
        foreach ($lines as $index => $line) {
            if (strcasecmp(trim($line), $label) === 0) {
                return $lines[$index + 1] ?? null;
            }
        }

        return null;
    }

    protected function recoveredIndexContaining(array $lines, string $label): ?int
    {
        foreach ($lines as $index => $line) {
            if (stripos($line, $label) !== false) {
                return $index;
            }
        }

        return null;
    }

    protected function extractRecoveredReportAndBudget(array $lines): array
    {
        $start = $this->recoveredIndexContaining($lines, 'REPORT No:');
        if ($start === null) {
            return [null, null];
        }

        $end = $this->nextRecoveredLabelIndex($lines, $start + 1, ['BWPD:']);
        $text = implode(' ', array_slice($lines, $start, $end - $start));

        if (!preg_match_all('/(?<![A-Za-z])\d[\d,]*(?:\.\d+)?/', $text, $matches)) {
            return [null, null];
        }

        $values = $matches[0];
        $reportNo = (int) str_replace(',', '', $values[0]);
        $budget = count($values) > 1 ? $this->toNumber(end($values)) : null;

        return [$reportNo, $budget];
    }

    protected function extractRecoveredNumberBetween(array $lines, string $startLabel, string $endLabel): ?float
    {
        $start = $this->recoveredIndexContaining($lines, $startLabel);
        if ($start === null) {
            return null;
        }

        $end = $this->nextRecoveredLabelIndex($lines, $start + 1, [$endLabel]);
        for ($index = $start; $index < $end; $index++) {
            $line = $index === $start
                ? substr($lines[$index], stripos($lines[$index], $startLabel) + strlen($startLabel))
                : $lines[$index];

            if (preg_match('/(?<![\d,])(\d[\d,]*(?:\.\d+)?)(?![\d,])/', $line, $match)) {
                return $this->toNumber($match[1]);
            }
        }

        return null;
    }

    protected function extractRecoveredSummary(array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            if (!preg_match('/Oper\.?\s*Summary\s*:/i', $line, $marker, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $parts = [];
            $markerPosition = $marker[0][1];
            $beforeMarker = trim(substr($line, 0, $markerPosition));

            if ($beforeMarker !== '' && $this->isNarrativeSummaryLine($beforeMarker)) {
                array_unshift($parts, $beforeMarker);
            }

            for ($previous = $index - 1; $previous >= 0; $previous--) {
                $candidate = trim($lines[$previous]);

                if (!$this->isNarrativeSummaryLine($candidate)) {
                    break;
                }

                array_unshift($parts, $candidate);
            }

            return $this->cleanText(implode(' ', $parts));
        }

        return null;
    }

    protected function nextRecoveredLabelIndex(array $lines, int $start, array $labels): int
    {
        for ($index = $start, $count = count($lines); $index < $count; $index++) {
            foreach ($labels as $label) {
                if (stripos($lines[$index], $label) !== false) {
                    return $index;
                }
            }
        }

        return count($lines);
    }
}

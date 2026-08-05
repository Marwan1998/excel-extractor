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

        foreach ($pdf->getPages() as $page) {
            $text = str_replace(["\r\n", "\r"], "\n", $page->getText());

            foreach (explode("\n", $text) as $line) {
                $line = trim($line);

                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $results = $this->parseLines($lines);

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

        if ($record['field_name'] === null && preg_match('/-(NC\d+)$/i', (string) $record['well_name'], $match)) {
            $record['field_name'] = strtoupper($match[1]);
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
}

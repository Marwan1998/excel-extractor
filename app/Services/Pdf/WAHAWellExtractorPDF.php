<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

/**
 * Extracts WAHA "Multi Well Management Summary Report - Drilling Section"
 * records from PDF files.
 */
class WAHAWellExtractorPDF
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): array
    {
        Log::debug('[WAHA PDF] Extractor started', ['file' => $filePath]);

        $pdf = $this->parser->parseFile($filePath);
        $lines = [];

        // Flatten every page before splitting records. Some WAHA well records
        // start near the end of one page and continue on the following page.
        foreach ($pdf->getPages() as $page) {
            $text = str_replace(["\r\n", "\r"], "\n", $page->getText());

            foreach (explode("\n", $text) as $line) {
                $line = trim(preg_replace('/[\t ]+/u', ' ', $line) ?? '');
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $results = [];

        foreach ($this->splitRecords($lines) as $record) {
            $results[] = $this->parseRecord($record['field_name'], $record['lines']);
        }

        Log::debug('[WAHA PDF] Extractor finished', ['rows' => count($results)]);

        return $results;
    }

    protected function splitRecords(array $lines): array
    {
        $records = [];
        $fieldName = null;
        $block = [];

        foreach ($lines as $index => $line) {
            $headerFieldName = $this->fieldNameAt($lines, $index);

            if ($headerFieldName !== null) {
                if ($fieldName !== null) {
                    $records[] = ['field_name' => $fieldName, 'lines' => $block];
                }

                $fieldName = $headerFieldName;
                $block = [];
                continue;
            }

            if ($fieldName !== null) {
                $block[] = $line;
            }
        }

        if ($fieldName !== null) {
            $records[] = ['field_name' => $fieldName, 'lines' => $block];
        }

        return $records;
    }

    protected function fieldNameAt(array $lines, int $index): ?string
    {
        if (
            !isset($lines[$index], $lines[$index + 1])
            || preg_match('/^([A-Z][A-Z0-9 &.]*)-\d+$/', $lines[$index], $match) !== 1
            || preg_match('/^WELL\s+NAME\s*:/i', $lines[$index + 1]) !== 1
        ) {
            return null;
        }

        return strtoupper(trim($match[1]));
    }

    protected function parseRecord(string $fieldName, array $lines): array
    {
        $text = implode("\n", $lines);
        $flat = preg_replace('/\s+/u', ' ', $text) ?? '';

        return [
            'field_name' => $fieldName,
            'well_name' => $this->extractTextBetween($flat, 'WELL NAME', 'PLANNED DAYS'),
            'budget' => $this->extractBudget($flat),
            'rig_name' => $this->extractRigName($flat),
            'objective' => $this->extractTextBetween($flat, 'OBJECTIVE', 'TD/TARGET'),
            'td_target' => $this->extractNumberAfterLabel($flat, 'TD/TARGET'),
            'current_depth' => $this->extractNumberAfterLabel($flat, 'CURRENT DEPTH'),
            'daily_footage' => $this->extractDailyFootage($flat),
            'spud_date' => $this->extractSpudDate($flat),
            'cumulative_cost' => $this->extractCumulativeCost($flat),
            'report_no' => $this->extractIntegerAfterLabel($flat, 'REPORT NO'),
            'summary' => $this->extractSummary($lines),
        ];
    }

    protected function extractTextBetween(string $text, string $startLabel, string $endLabel): ?string
    {
        $pattern = sprintf(
            '/%s\s*:\s*(.*?)\s+%s\s*:/i',
            preg_quote($startLabel, '/'),
            preg_quote($endLabel, '/')
        );

        if (!preg_match($pattern, $text, $match)) {
            return null;
        }

        return $this->cleanText($match[1]);
    }

    protected function extractBudget(string $text): ?float
    {
        if (!preg_match('/BUDGET\s*:\s*\(\$\)(.*?)\s+OBJECTIVE\s*:/i', $text, $match)) {
            return null;
        }

        preg_match_all('/\d[\d,]*(?:\.\d+)?/', $match[1], $numbers);

        if ($numbers[0] === []) {
            return null;
        }

        return $this->toNumber(end($numbers[0]));
    }

    protected function extractRigName(string $text): ?string
    {
        if (!preg_match('/CONTR\s*\/\s*RIG\s+NO\s*:\s*(.*?)\s*\(ft\)/i', $text, $match)) {
            return null;
        }

        return $this->cleanText($match[1]);
    }

    protected function extractNumberAfterLabel(string $text, string $label): ?float
    {
        $pattern = '/'.preg_quote($label, '/').'\s*:\s*([\d,]+(?:\.\d+)?)/i';

        if (!preg_match($pattern, $text, $match)) {
            return null;
        }

        return $this->toNumber($match[1]);
    }

    protected function extractIntegerAfterLabel(string $text, string $label): ?int
    {
        $pattern = '/'.preg_quote($label, '/').'\s*:?\s*(\d+)/i';

        if (!preg_match($pattern, $text, $match)) {
            return null;
        }

        return (int) $match[1];
    }

    protected function extractDailyFootage(string $text): ?float
    {
        if (!preg_match('/DAILY\s+FOOTAGE\s*:\s*([^()]*)\(ft\)/i', $text, $match)) {
            return null;
        }

        if (!preg_match('/([\d,]+(?:\.\d+)?)/', $match[1], $number)) {
            return null;
        }

        return $this->toNumber($number[1]);
    }

    protected function extractSpudDate(string $text): ?string
    {
        if (!preg_match(
            '/SPUD\s+IN\s+DATE\s*:\s*(\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[\/-](?:\d{1,2}|[A-Za-z]{3,9})[\/-]\d{2,4})/i',
            $text,
            $match
        )) {
            return null;
        }

        return $match[1];
    }

    protected function extractCumulativeCost(string $text): ?float
    {
        if (!preg_match('/CUM\s+COST\s*:(.*?)(?:PRESENT\s+OPERATIONS|24\s+HRS\s*-\s*SUMMARY)/i', $text, $match)) {
            return null;
        }

        if (!preg_match('/\$\s*([\d,]+(?:\.\d+)?)/', $match[1], $cost)) {
            return null;
        }

        return $this->toNumber($cost[1]);
    }

    protected function extractSummary(array $lines): ?string
    {
        $parts = [];
        $collect = false;

        foreach ($lines as $line) {
            if (!$collect && preg_match('/24\s+HRS\s*-\s*SUMMARY\s*:\s*(.*)$/i', $line, $match)) {
                $collect = true;
                if (trim($match[1]) !== '') {
                    $parts[] = trim($match[1]);
                }
                continue;
            }

            if (!$collect) {
                continue;
            }

            if (preg_match('/^(.*?)\bFORECAST\s*:/i', $line, $match)) {
                if (trim($match[1]) !== '') {
                    $parts[] = trim($match[1]);
                }
                break;
            }

            if ($this->isSummaryNoise($line)) {
                continue;
            }

            $parts[] = $line;
        }

        return $this->cleanText(implode(' ', $parts));
    }

    protected function isSummaryNoise(string $line): bool
    {
        return preg_match('/^WAHA\s+OIL\s+COMPANY$/i', $line) === 1
            || stripos($line, 'OpenWells Reporting System') !== false
            || preg_match('/^Multi\s+Well\s+Management\s+Summary\s+Report/i', $line) === 1;
    }

    protected function toNumber(string $value): float
    {
        return (float) str_replace(',', '', $value);
    }

    protected function cleanText(string $text): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text !== '' ? $text : null;
    }
}

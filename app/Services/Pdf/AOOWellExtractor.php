<?php

namespace App\Services\Pdf;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class AOOWellExtractor
{
    protected $parser;

    public function __construct($parser = null)
    {
        $this->parser = $parser ?: new Parser();
    }

    public function extract(string $filePath): array
    {
        Log::debug('AOO Well Extractor Started', [
            'file' => $filePath,
        ]);

        $pdf = $this->parser->parseFile($filePath);

        $rawText = '';

        foreach ($pdf->getPages() as $index => $page) {
            $pageText = $page->getText();

            /*
             * Very important:
             * Some AOO PDFs are extracted by Smalot like:
             * 
             * instead of:
             * Well Name:
             */
            $pageText = $this->decodePrivateUseText($pageText);

            Log::debug('AOO Page Text', [
                'page' => $index + 1,
                'length' => strlen($pageText),
                'well_name_count' => substr_count(strtolower($pageText), 'well name'),
                'preview' => substr(preg_replace('/\s+/', ' ', $pageText), 0, 800),
            ]);

            $rawText .= "\n" . $pageText;
        }

        $text = $this->normalizeText($rawText);

        Log::debug('AOO Full Text', [
            'length' => strlen($text),
            'well_name_count' => substr_count(strtolower($text), 'well name'),
            'preview' => substr($this->flatten($text), 0, 1500),
        ]);

        $records = $this->parseByWellNameOffsets($text);

        Log::debug('AOO Extractor Finished', [
            'records_count' => count($records),
            'well_names' => array_column($records, 'well_name'),
        ]);

        return $records;
    }

    private function parseByWellNameOffsets(string $text): array
    {
        preg_match_all('/\bWell\s*Name\s*:/iu', $text, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            Log::warning('AOO Extractor found no Well Name markers.');
            return [];
        }

        $wellOffsets = [];

        foreach ($matches[0] as $match) {
            $wellOffsets[] = $match[1];
        }

        $records = [];
        $currentField = null;

        for ($i = 0; $i < count($wellOffsets); $i++) {
            $wellOffset = $wellOffsets[$i];
            $nextWellOffset = $wellOffsets[$i + 1] ?? strlen($text);

            $blockStart = $this->detectBlockStart($text, $wellOffset);
            $blockEnd = $this->detectBlockEnd($text, $nextWellOffset);

            if ($blockEnd <= $blockStart) {
                $blockEnd = $nextWellOffset;
            }

            $field = $this->detectFieldBeforeOffset($text, $wellOffset, $currentField);

            if ($field !== null) {
                $currentField = $field;
            }

            $block = substr($text, $blockStart, $blockEnd - $blockStart);

            $record = $this->parseWellBlock($block, $currentField);

            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $records;
    }

    private function parseWellBlock(string $block, $fieldName)
    {
        $flat = $this->flatten($block);

        $wellName = $this->extractText(
            $flat,
            '/\bWell\s*Name\s*:\s*(.*?)(?=\s+(?:DATE|Rig\s*Name|OBJECTIVE|K\.B|REPORT\s*No|SPUD\s+DATE|CURRENT\s+DEPTH)\s*:)/iu'
        );

        if (!$wellName) {
            $wellName = $this->extractText(
                $flat,
                '/\bWell\s*Name\s*:\s*([A-Z0-9][A-Z0-9&\/\.\-\s]*)/iu'
            );
        }

        if (!$wellName) {
            Log::warning('AOO skipped block: well name not found', [
                'block_preview' => substr($flat, 0, 1000),
            ]);

            return null;
        }

        $rigCell = $this->extractText(
            $flat,
            '/\bRig\s*Name\s*\/?\s*NO\.?\s*:\s*(.*?)(?=\s+AFE\s*:)/iu'
        );

        $afeCell = $this->extractText(
            $flat,
            '/\bAFE\s*:\s*(.*?)(?=\s+(?:OBJECTIVE|K\.B|REPORT\s*No|SPUD\s+DATE|START\s+OPERATION|CURRENT\s+DEPTH|PREVIOUS\s+DEPTH|FOOTAGE)\s*:)/iu'
        );

        $objectiveCell = $this->extractText(
            $flat,
            '/\bOBJECTIVE\s*:\s*(.*?)(?=\s+K\.B\s*:|\s+REPORT\s*No\s*:|\s+G\.L\s*:|\s+SPUD\s+DATE\s*:|\s+START\s+OPERATION\s*:|\s+CURRENT\s+DEPTH\s*:)/iu'
        );

        $rigAndObjective = $this->detectRigAndObjective($rigCell, $afeCell, $objectiveCell);

        $currentDepth = $this->extractTableNumber($flat, 'CURRENT DEPTH');
        $previousDepth = $this->extractTableNumber($flat, 'PREVIOUS DEPTH');
        $footage = $this->extractTableNumber($flat, 'FOOTAGE');

        /*
         * Some April PDFs show FOOTAGE as empty because Smalot reverses table cells.
         * If current and previous depth exist, calculate footage.
         */
        if ($footage === null && $currentDepth !== null && $previousDepth !== null) {
            $footage = $currentDepth - $previousDepth;
        }

        return [
            'field_name'    => $this->cleanFieldName($fieldName),
            'well_name'     => $this->cleanValue($wellName),
            'rig_name'      => $rigAndObjective['rig_name'],
            'objective'     => $rigAndObjective['objective'],
            'current_depth' => $currentDepth,
            'footage'       => $footage,
            'spud_date'     => $this->extractSpudDate($flat),
            'cum_cost'      => $this->extractTableNumber($flat, 'CUM. COST'),
            'summary'       => $this->extractOperSummary($block),
        ];
    }

    private function detectRigAndObjective($rigCell, $afeCell, $objectiveCell): array
    {
        $rigCell = $this->cleanValue($rigCell);
        $afeCell = $this->cleanValue($afeCell);
        $objectiveCell = $this->cleanObjective($objectiveCell);

        /*
         * Normal layout, like July PDFs:
         * Rig Name / NO.: AL-LAHEEB/01
         * AFE: AOO-IR-R-PD...
         * OBJECTIVE: VERTICAL WELL
         */
        if ($rigCell && !preg_match('/^AOO-/iu', $rigCell)) {
            return [
                'rig_name' => $rigCell,
                'objective' => $objectiveCell,
            ];
        }

        /*
         * Swapped layout, like some April PDFs:
         * Rig Name / NO.: AOO-IR-ALL-PD...
         * AFE: ADWOC/17 DEV-VERTICAL
         * OBJECTIVE:
         */
        if ($rigCell && preg_match('/^AOO-/iu', $rigCell) && $afeCell) {
            $fromAfe = $this->splitRigAndObjectiveFromAfeCell($afeCell);

            return [
                'rig_name' => $fromAfe['rig_name'],
                'objective' => $fromAfe['objective'] ?: $objectiveCell,
            ];
        }

        if ($afeCell) {
            $fromAfe = $this->splitRigAndObjectiveFromAfeCell($afeCell);

            return [
                'rig_name' => $fromAfe['rig_name'],
                'objective' => $fromAfe['objective'] ?: $objectiveCell,
            ];
        }

        return [
            'rig_name' => $rigCell,
            'objective' => $objectiveCell,
        ];
    }

    private function splitRigAndObjectiveFromAfeCell($afeCell): array
    {
        $afeCell = $this->cleanValue($afeCell);

        if (!$afeCell) {
            return [
                'rig_name' => null,
                'objective' => null,
            ];
        }

        /*
         * Examples:
         * ADWOC/17 DEV-VERTICAL
         * AL-LAHEEB/01 DEV-VERTICAL
         * NDC/07 DEV-HORIZONTAL
         * ADWOC/17 Injection Well
         */
        if (preg_match('/^(.+?\/\s*\d{1,3})\s+(.+)$/iu', $afeCell, $matches)) {
            return [
                'rig_name' => $this->cleanValue($matches[1]),
                'objective' => $this->cleanObjective($matches[2]),
            ];
        }

        if (preg_match('/^(.+?\/\s*\d{1,3})$/iu', $afeCell, $matches)) {
            return [
                'rig_name' => $this->cleanValue($matches[1]),
                'objective' => null,
            ];
        }

        return [
            'rig_name' => $afeCell,
            'objective' => null,
        ];
    }

    private function extractTableNumber(string $text, string $label)
    {
        $labelRegex = preg_quote($label, '/');
        $labelRegex = str_replace('\ ', '\s+', $labelRegex);

        if ($label === 'CUM. COST') {
            $labelRegex = 'CUM\.?\s*COST';
        }

        /*
         * Normal:
         * CURRENT DEPTH: 1,962.00
         * FOOTAGE: 762.00
         * CUM. COST: 921,270.84
         */
        if (preg_match('/\b' . $labelRegex . '\s*:\s*([0-9][0-9,]*(?:\.\d+)?)/iu', $text, $matches)) {
            return $this->cleanNumber($matches[1]);
        }

        /*
         * Swapped / cell-order case:
         * CURRENT DEPTH: (ft) END DATE: 5,042.00 (ft)
         */
        if (preg_match('/\b' . $labelRegex . '\s*:\s*\([^)]+\)\s+[A-Z\s\/\.]+:\s*([0-9][0-9,]*(?:\.\d+)?)/iu', $text, $matches)) {
            return $this->cleanNumber($matches[1]);
        }

        return null;
    }

    private function extractSpudDate(string $text)
    {
        if (!preg_match('/\bSPUD\s+DATE\s*:\s*([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}|[0-9]{4}-[0-9]{2}-[0-9]{2}|[0-9]{1,2}-[A-Za-z]{3}-[0-9]{4})/iu', $text, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function extractOperSummary(string $block)
    {
        $text = $this->normalizeText($block);

        /*
         * Case 1:
         * Oper. Summary:
         * actual summary...
         * Next Operations:
         */
        if (preg_match('/\bOper\.?\s*Summary\s*:\s*(.*?)(?=\n\s*(?:Next Operations|General Notes|RIG SUPERVISOR|DATE|Well Name)\s*:|\z)/isu', $text, $matches)) {
            $summary = $this->cleanSummary($matches[1] ?? '');

            if ($summary !== '') {
                return $summary;
            }
        }

        /*
         * Case 2:
         * Present Operations: current operation
         * actual summary text...
         * Oper. Summary:
         */
        if (preg_match('/\bPresent Operations\s*:\s*[^\n]*(?:\n|$)(.*?)(?=\n\s*Oper\.?\s*Summary\s*:)/isu', $text, $matches)) {
            $summary = $this->cleanSummary($matches[1] ?? '');

            if ($summary !== '') {
                return $summary;
            }
        }

        /*
         * Flat fallback.
         */
        $flat = $this->flatten($block);

        if (preg_match('/\bPresent Operations\s*:\s*.*?\s+(.*?)(?=\s+Oper\.?\s*Summary\s*:)/isu', $flat, $matches)) {
            $summary = $this->cleanSummary($matches[1] ?? '');

            if ($summary !== '') {
                return $summary;
            }
        }

        return null;
    }

    private function cleanSummary(string $summary): string
    {
        $summary = trim($summary);

        if ($summary === '') {
            return '';
        }

        $summary = preg_replace('/\b(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday),\s+.*?\bPage\s+\d+\s+of\s+\d+\b.*?AKAKUS OIL OPERATIONS\b/iu', ' ', $summary);
        $summary = preg_replace('/\bDRILLING\s*&\s*WORKOVER\s*DEPARTMENT\b/iu', ' ', $summary);

        $summary = preg_replace('/\s*\n\s*/u', ' ', $summary);
        $summary = preg_replace('/\s{2,}/u', ' ', $summary);

        $summary = preg_replace('/^\s*Oper\.?\s*Summary\s*:\s*/iu', '', $summary);
        $summary = preg_replace('/\s*(Next Operations|General Notes|RIG SUPERVISOR)\s*:.*$/isu', '', $summary);

        return trim($summary);
    }

    private function detectBlockStart(string $text, int $wellOffset): int
    {
        /*
         * July-style:
         * DATE: 05-Jul-2026 Well Name: R48-I&R
         *
         * April-style:
         * Well Name: I32-NC186 DATE: 08-Apr-2026
         */
        $start = max(0, $wellOffset - 150);
        $prefix = substr($text, $start, $wellOffset - $start);

        if (preg_match('/DATE\s*:\s*(?:\d{1,2}-[A-Za-z]{3}-\d{4}|\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})\s*$/iu', $prefix, $matches, PREG_OFFSET_CAPTURE)) {
            return $start + $matches[0][1];
        }

        return $wellOffset;
    }

    private function detectBlockEnd(string $text, int $nextWellOffset): int
    {
        /*
         * If next well has DATE before Well Name, stop before DATE.
         */
        $start = max(0, $nextWellOffset - 150);
        $prefix = substr($text, $start, $nextWellOffset - $start);

        if (preg_match('/DATE\s*:\s*(?:\d{1,2}-[A-Za-z]{3}-\d{4}|\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})\s*$/iu', $prefix, $matches, PREG_OFFSET_CAPTURE)) {
            return $start + $matches[0][1];
        }

        return $nextWellOffset;
    }

    private function detectFieldBeforeOffset(string $text, int $offset, $currentField = null)
    {
        $start = max(0, $offset - 700);
        $tail = substr($text, $start, $offset - $start);
        $tail = $this->flatten($tail);

        /*
         * Best pattern:
         * DRILLING & WORKOVER DEPARTMENT I&R Well Name:
         * DRILLING & WORKOVER DEPARTMENT NC115 Well Name:
         */
        if (preg_match_all('/DRILLING\s*&\s*WORKOVER\s*DEPARTMENT\s+(NC\s*I&R|I&R|NC\s*\d+|NC\d+)/iu', $tail, $matches)) {
            $candidate = end($matches[1]);
            $candidate = $this->cleanFieldName($candidate);

            if ($this->looksLikeFieldName($candidate)) {
                return $candidate;
            }
        }

        /*
         * Fallback:
         * NC115 Well Name:
         * I&R Well Name:
         */
        if (preg_match_all('/(?:^|\s)(NC\s*I&R|I&R|NC\s*\d+|NC\d+)\s+(?=Well\s*Name\s*:|DATE\s*:|$)/iu', $tail, $matches)) {
            $candidate = end($matches[1]);
            $candidate = $this->cleanFieldName($candidate);

            if ($this->looksLikeFieldName($candidate)) {
                return $candidate;
            }
        }

        return $currentField;
    }

    private function looksLikeFieldName($line): bool
    {
        $line = $this->cleanValue($line);

        if (!$line) {
            return false;
        }

        if (strpos($line, ':') !== false) {
            return false;
        }

        if (strlen($line) > 35) {
            return false;
        }

        if (preg_match('/\b(DRILLING|WORKOVER|DEPARTMENT|AKAKUS|OPERATIONS|SUMMARY|REPORT|MUD|DATA|TYPE|VIS|PAGE|DATE|OBJECTIVE|SUPERVISOR|GEOLOGIEST|WELL|ENG|CONFIDENTIAL|PROPRIETARY)\b/iu', $line)) {
            return false;
        }

        return (bool) preg_match('/^(NC\s*I&R|I&R|NC\s*\d+|NC\d+|[A-Z0-9&\/\-]{2,25})$/iu', $line);
    }

    private function extractText(string $text, string $pattern)
    {
        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        return $this->cleanValue($matches[1] ?? null);
    }

    private function cleanObjective($value)
    {
        $value = $this->cleanValue($value);

        if (!$value) {
            return null;
        }

        /*
         * Never accept table labels as objective.
         */
        if (preg_match('/^(K\.B|G\.L|REPORT|SPUD|CURRENT|PREVIOUS|FOOTAGE|DOL|FORMATION|END DATE)\b/iu', $value)) {
            return null;
        }

        /*
         * Never accept AFE code as objective.
         */
        if (preg_match('/^AOO-/iu', $value)) {
            return null;
        }

        return $value;
    }

    private function cleanFieldName($fieldName)
    {
        $fieldName = $this->cleanValue($fieldName);

        if (!$fieldName) {
            return null;
        }

        return preg_replace('/\s+/u', ' ', $fieldName);
    }

    private function cleanValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = $this->decodePrivateUseText((string) $value);
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', $value);

        if ($value === null) {
            return null;
        }

        return trim($value);
    }

    private function cleanNumber($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '', $value);

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (int) $number : $number;
    }

    private function normalizeText(string $text): string
    {
        $text = $this->decodePrivateUseText($text);
        $text = $this->fixInvalidUtf8($text);

        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        if ($text === null) {
            return '';
        }

        $text = preg_replace('/^\s+|\s+$/m', '', $text);
        if ($text === null) {
            return '';
        }

        $text = preg_replace("/\n{2,}/u", "\n", $text);
        if ($text === null) {
            return '';
        }

        return trim($text);
    }

    private function flatten(string $text): string
    {
        $text = $this->normalizeText($text);

        $flat = preg_replace('/\s+/u', ' ', $text);

        return trim($flat !== null ? $flat : $text);
    }

    private function decodePrivateUseText(string $text): string
    {
        $text = $this->fixInvalidUtf8($text);

        $decoded = preg_replace_callback('/[\x{F000}-\x{F0FF}]/u', function ($match) {
            $code = $this->unicodeOrd($match[0]);
            $asciiCode = $code - 0xF000;

            if ($asciiCode >= 0 && $asciiCode <= 255) {
                return chr($asciiCode);
            }

            return $match[0];
        }, $text);

        return $decoded !== null ? $decoded : $text;
    }

    private function fixInvalidUtf8(string $text): string
    {
        if (function_exists('mb_check_encoding') && mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return $fixed !== false ? $fixed : $text;
    }

    private function unicodeOrd(string $char): int
    {
        $converted = @mb_convert_encoding($char, 'UCS-4BE', 'UTF-8');

        if ($converted === false || strlen($converted) < 4) {
            return 0;
        }

        $result = unpack('N', $converted);

        return $result ? $result[1] : 0;
    }
}
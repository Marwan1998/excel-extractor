<?php

namespace App\Services\Word;

use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class SOCWellExtractorWorkover
{
    protected array $labelMap = [
        'PBTD'         => 'TD/TARGET',
        'DAY'          => 'DAY',
        'RIG'          => 'CONTR/RIG NO',
        'WELL'         => 'WELL NAME',
        'CUM.COST'     => 'CUM COST',
        'DAILY COST'   => 'DAILY COST',
    ];

    public function extract(string $filePath): array
    {
        Log::debug('DOCX extractor started', ['file' => $filePath]);

        $phpWord = IOFactory::load($filePath);
        $records = [];
        $startExtraction = false;

        foreach ($phpWord->getSections() as $sectionIndex => $section) {
            foreach ($section->getElements() as $elementIndex => $element) {

                if (!method_exists($element, 'getRows')) {
                    continue;
                }

                $rows = $element->getRows();
                // Log::debug('Processing table', [
                //     'section' => $sectionIndex,
                //     'element' => $elementIndex,
                //     'rows' => count($rows)
                // ]);

                for ($i = 0; $i < count($rows); $i++) {

                    $row = $this->getRowText($rows[$i]);

                    // Log::debug('Row content', [
                    //     'row_index' => $i,
                    //     'data' => $row
                    // ]);

                    // START after WORKOVER
                    if (!$startExtraction && $this->containsKeyword($row, 'WORKOVER ACTIVITIES')) {
                        $startExtraction = true;
                        // Log::debug('WORKOVER section detected');
                        continue;
                    }

                    if (!$startExtraction) continue;

                    // detect well row (contains PBTD + WELL)
                    if (!$this->isWellRow($row)) continue;

                    // Log::debug('Well row detected', $row);

                    $record = [
                        'TD/TARGET'     => '',
                        'DAY'           => '',
                        'CONTR/RIG NO'  => '',
                        'WELL NAME'     => '',
                        'CUM COST'      => '',
                        'DAILY COST'    => '',
                        'SUMMARY'       => '',
                    ];

                    // ---------- EXTRACT FROM SAME ROW ----------
                    foreach ($row as $cell) {

                        // TD
                        if (strpos($cell, 'PBTD') !== false) {
                            $record['TD/TARGET'] = $this->extractNumber($cell);
                        }

                        // DAY (numeric or letter)
                        if (preg_match('/^\d+$|^[A-Z]+$/', trim($cell))) {
                            $record['DAY'] = trim($cell);
                        }

                        // RIG
                        if (strpos($cell, '#') !== false) {
                            $record['CONTR/RIG NO'] = trim($cell);
                        }

                        // WELL Name
                        foreach ($row as $index => $cell) {

                            // WELL detection using label position (NOT pattern)
                            if (stripos($cell, '(WELL') !== false) {
                                $value = $row[$index - 1] ?? '';

                                $record['WELL NAME'] = trim(preg_replace('/\s+/', ' ', $value));
                            }
                        }

                    }

                    // ---------- SUMMARY ----------
                    $summaryRow = $this->getRowText($rows[$i + 1] ?? null);
                    $record['SUMMARY'] = trim(implode(' ', $summaryRow));

                    // ---------- COST ROW ----------
                    $costRow = $this->getRowText($rows[$i + 2] ?? null);

                    // Log::debug('Cost row detected', $costRow);

                    foreach ($costRow as $index => $cell) {

                        // detect CUM COST
                        if (stripos($cell, 'CUM.COST') !== false) {
                            $value = $costRow[$index - 1] ?? $costRow[$index + 1] ?? '';
                            $record['CUM COST'] = $this->cleanValue($value);
                        }

                        // detect DAILY COST
                        if (stripos($cell, 'DAILY COST') !== false || stripos($cell, 'DAILYCOST') !== false) {
                            $value = $costRow[$index - 1] ?? $costRow[$index + 1] ?? '';
                            $record['DAILY COST'] = $this->cleanValue($value);
                        }
                    }

                    // Log::debug('Record built', $record);

                    $records[] = $record;

                    // move to next block
                    $i += 2;
                }
            }
        }

        Log::debug('DOCX extraction completed', [
            'records_count' => count($records)
        ]);

        return $records;
    }

    // ---------------- HELPERS ----------------

    protected function getRowText($row): array
    {
        if (!$row) return [];

        $cellsText = [];

        foreach ($row->getCells() as $cell) {
            $text = '';
            foreach ($cell->getElements() as $el) {
                if (method_exists($el, 'getText')) {
                    $text .= ' ' . $el->getText();
                }
            }
            $cellsText[] = trim(html_entity_decode($text));
        }

        return $cellsText;
    }

    protected function isWellRow(array $row): bool
    {
        $hasPBTD = false;
        $hasWell = false;

        foreach ($row as $cell) {
            if (stripos($cell, 'PBTD') !== false) $hasPBTD = true;
            if (stripos($cell, 'WELL') !== false) $hasWell = true;
        }

        return $hasPBTD && $hasWell;
    }

    protected function containsKeyword(array $row, string $keyword): bool
    {
        foreach ($row as $cell) {
            if (stripos($cell, $keyword) !== false) return true;
        }
        return false;
    }

    protected function extractNumber(string $text): string
    {
        if (preg_match('/\d+/', $text, $m)) {
            return $m[0];
        }
        return '';
    }

    protected function cleanValue(string $value): string
    {
        $value = trim($value);

        // remove Arabic text
        $value = preg_replace('/[^\x00-\x7F]/', '', $value);

        return trim($value);
    }
}
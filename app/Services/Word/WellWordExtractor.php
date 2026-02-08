<?php

namespace App\Services\Word;

use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class WellWordExtractor
{
    protected array $labelMap = [
        'TD'         => 'TD/TARGET',
        'PDBT'       => 'CURRENT DEPTH',
        'PD'         => 'CURRENT DEPTH',
        // 'KB'         => 'KB',
        'PROG'       => 'PROG',
        'DAY'        => 'DAY',
        'RIG'        => 'CONTR/RIG NO',
        'WELL'       => 'WELL NAME',
        'CUM.COST'   => 'CUM COST',
        'DAILY COST' => 'DAILY COST',
    ];

    public function extract(string $filePath): array
    {
        Log::debug('DOCX extractor started', ['file' => $filePath]);

        $phpWord = IOFactory::load($filePath);
        $records = [];
        $stopExtraction = false;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {

                if (!method_exists($element, 'getRows')) {
                    continue;
                }

                $rows = $element->getRows();
                $rowCount = count($rows);

                for ($i = 0; $i < $rowCount; $i++) {

                    $currentRowText = $this->getRowText($rows[$i]);

                    // 🛑 STOP EVERYTHING CONDITION
                    if ($this->containsStopKeyword($currentRowText)) {
                        Log::debug('STOP keyword detected. Extraction halted.', [
                            'row' => $currentRowText
                        ]);
                        $stopExtraction = true;
                        break 2; // exit rows + tables
                    }

                    // ---------- ROW 1: KEYS ----------
                    if (!$this->isKeysRow($currentRowText)) {
                        continue;
                    }

                    // Log::debug('Keys row detected', $currentRowText);

                    // ---------- ROW 2: VALUES ----------
                    $valueRow   = $this->getRowText($rows[$i + 1] ?? null);

                    // ---------- ROW 3: SUMMARY ----------
                    $summaryRow = $this->getRowText($rows[$i + 2] ?? null);

                    // ---------- ROW 4: MIXED ----------
                    $mixedRow   = $this->getRowText($rows[$i + 3] ?? null);

                    $record = [];

                    // Map keys → values
                    foreach ($currentRowText as $index => $rawKey) {
                        $label = $this->normalizeKey($rawKey);
                        if (!$label) {
                            continue;
                        }

                        $record[$label] = trim($valueRow[$index] ?? '');
                    }

                    // Summary row (single logical cell)
                    if (!empty($summaryRow)) {
                        $record['SUMMARY'] = trim(implode(' ', $summaryRow));
                    }

                    // Mixed row (value, key pattern)
                    for ($x = 0; $x < count($mixedRow) - 1; $x += 2) {
                        $value = trim($mixedRow[$x]);
                        $key   = $this->normalizeKey($mixedRow[$x + 1] ?? '');

                        if ($key && $value !== '') {
                            $record[$key] = $value;
                        }
                    }

                    // Log::debug('Record built', $record);
                    $records[] = $record;

                    // Skip consumed rows
                    $i += 3;
                }
            }
        }

        Log::debug('DOCX extraction completed', [
            'records' => count($records),
            'stopped' => $stopExtraction
        ]);

        return $records;
    }

    // ---------------- HELPERS ----------------

    protected function getRowText($row): array
    {
        if (!$row) {
            return [];
        }

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

        return array_values(array_filter($cellsText, fn ($v) => $v !== ''));
    }

    protected function isKeysRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (preg_match('/\(TD\)|\(KB\)|\(RIG\)|\(WELL\)/i', $cell)) {
                return true;
            }
        }
        return false;
    }

    protected function normalizeKey(string $raw): ?string
    {
        foreach ($this->labelMap as $needle => $label) {
            if (stripos($raw, $needle) !== false) {
                return $label;
            }
        }
        return null;
    }

    protected function containsStopKeyword(array $row): bool
    {
        foreach ($row as $cell) {
            if (stripos($cell, 'WORKOVER ACTIVITIES') !== false) {
                return true;
            }
        }
        return false;
    }
}

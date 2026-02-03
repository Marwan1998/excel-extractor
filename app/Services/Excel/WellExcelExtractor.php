<?php

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WellExcelExtractor
{
    protected array $inlineTextRules;
    protected array $standaloneKeys;
    protected array $columnInlineKeys;
    protected array $recordTerminators;

    public function __construct()
    {
        $map = require app_path('Services/Excel/WellLabelMap.php');

        $this->inlineTextRules  = $map['INLINE_TEXT'];
        $this->standaloneKeys  = $map['STANDALONE'];
        $this->columnInlineKeys = $map['COLUMN_INLINE'];
        $this->recordTerminators = $map['record_terminators'] ?? [];

    }

    public function extract(string $filePath): array
    {
        $sheet = IOFactory::load($filePath)->getActiveSheet();

        $results = [];
        $current = [];
        $expecting = null;
        $columnWatch = [];

        foreach ($sheet->getRowIterator() as $row) {

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {

                $raw = trim((string)$cell->getValue());
                if ($raw === '') {
                    continue;
                }


                // ✅ TERMINATION BLOCK
                $upperRaw = strtoupper(rtrim($raw, ':'));

                if (in_array($upperRaw, $this->recordTerminators, true)) {

                    // ✅ End current record safely
                    if (!empty($current)) {
                        $results[] = $current;
                    }

                    // 🔄 Reset all state
                    $current = [];
                    $expecting = null;
                    $columnWatch = [];

                    // ⛔ Do NOT treat this cell as data
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | 1️⃣ INLINE TEXT RULES (WELL NAME etc.)
                |--------------------------------------------------------------------------
                */
                foreach ($this->inlineTextRules as $key => $regex) {
                    if (preg_match($regex, $raw, $m)) {

                        // 🔴 WELL NAME = HARD RECORD BOUNDARY
                        if ($key === 'WELL NAME') {
                            if (!empty($current)) {
                                $results[] = $current;
                            }

                            $current = [];
                            $columnWatch = [];
                        }

                        $current[$key] = $m[1];
                        continue 2;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 2️⃣ COLUMN INLINE HEADERS (CUM COST)
                |--------------------------------------------------------------------------
                */
                $upper = strtoupper(rtrim($raw, ':'));

                if (in_array($upper, $this->columnInlineKeys, true)) {
                    $columnWatch[$upper] = $cell->getColumn();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | 3️⃣ STANDALONE LABEL
                |--------------------------------------------------------------------------
                */
                if (in_array($upper, $this->standaloneKeys, true)) {
                    $expecting = $upper;
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | 4️⃣ COLUMN INLINE VALUES
                |--------------------------------------------------------------------------
                */
                foreach ($columnWatch as $key => $column) {
                    if (
                        $cell->getColumn() === $column &&
                        is_numeric($raw) &&
                        !isset($current[$key])
                    ) {
                        $current[$key] = $raw;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 5️⃣ VALUE FOR STANDALONE LABEL
                |--------------------------------------------------------------------------
                */
                if ($expecting) {

                    // ❌ Ignore garbage / units
                    if (
                        preg_match('/^\(.*\)$/', $raw) ||
                        str_contains(strtoupper($raw), 'HOLE SIZE')
                    ) {
                        $current[$expecting] = '';
                    }

                    // 📅 Excel date conversion
                    elseif (
                        in_array($expecting, ['DATE', 'SPUD IN DATE'], true) &&
                        is_numeric($cell->getValue())
                    ) {
                        $current[$expecting] =
                            ExcelDate::excelToDateTimeObject($cell->getValue())
                                ->format('Y/m/d');
                    }

                    // ✅ Normal value
                    else {
                        $current[$expecting] = $raw;
                    }

                    $expecting = null;
                }
            }
        }

        // Push last well
        if (!empty($current)) {
            $results[] = $current;
        }

        return $results;
    }

}

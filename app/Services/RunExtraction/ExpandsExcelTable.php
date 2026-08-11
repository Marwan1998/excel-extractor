<?php

namespace App\Services\RunExtraction;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait ExpandsExcelTable
{
    protected function expandExcelTableToRow(
        Worksheet $sheet,
        int $lastRow,
        int $minimumColumnCount
    ): void {
        $candidate = null;
        $candidateEndRow = -1;

        foreach ($sheet->getTableCollection() as $table) {
            [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($table->getRange());

            if (
                $rangeStart[0] !== 1
                || $rangeEnd[0] < $minimumColumnCount
                || $rangeEnd[1] > $lastRow
            ) {
                continue;
            }

            if ($rangeEnd[1] > $candidateEndRow) {
                $candidate = $table;
                $candidateEndRow = $rangeEnd[1];
            }
        }

        if ($candidate === null || $candidateEndRow === $lastRow) {
            return;
        }

        $expandedRange = preg_replace('/\d+$/', (string) $lastRow, $candidate->getRange());

        if (is_string($expandedRange)) {
            $candidate->setRange($expandedRange);
        }
    }
}

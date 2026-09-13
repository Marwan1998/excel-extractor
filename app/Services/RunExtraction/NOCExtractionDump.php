<?php

namespace App\Services\RunExtraction;

use App\Services\Pdf\NOCWellExtractor;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class NOCExtractionDump
{
    use ExpandsExcelTable;

    private const CLEAN_WELL_NAME = true;

    public function runExtraction(array $filesData): array
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $records = (new NOCWellExtractor())->extract($filePath);
            $workbookPath = storage_path('app/DDR.xlsx');
            $spreadsheet = IOFactory::load($workbookPath);
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = $sheet->getHighestRow() + 1;

            foreach ($records as $record) {
                $reportDate = $this->excelDate($record['report_date'] ?? null);
                $spudDate = $this->excelDate($record['spud_date'] ?? null);

                $sheet->setCellValue("A{$startRow}", $record['company_name'] ?? '');
                $sheet->setCellValue("B{$startRow}", $reportDate ?? '');
                $sheet->getStyle("B{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');
                $sheet->setCellValue("C{$startRow}", $record['report_no'] ?? '');
                $sheet->setCellValue("D{$startRow}", $record['field_name'] ?? '');
                $sheet->setCellValue("E{$startRow}", $this->cleanWellName($record['well_name'] ?? ''));
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');
                $sheet->setCellValue("H{$startRow}", $spudDate ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');
                $sheet->setCellValue("I{$startRow}", $record['target_depth'] ?? 0);
                $sheet->setCellValue("J{$startRow}", $record['progress'] ?? 0);
                $sheet->setCellValue("K{$startRow}", $record['current_depth'] ?? 0);
                $sheet->setCellValue("L{$startRow}", $record['budget'] ?? 0);
                $sheet->setCellValue("M{$startRow}", $record['cumulative_cost'] ?? 0);
                $sheet->setCellValue("N{$startRow}", $record['summary'] ?? '');
                $startRow++;
            }

            $this->expandExcelTableToRow($sheet, $startRow - 1, 14);
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($workbookPath);

            $dataAdded[] = [
                'file-name' => $value['name'],
                'count' => count($records),
                'date' => $records[0]['report_date'] ?? null,
            ];
        }

        return $dataAdded;
    }

    private function excelDate(?string $value)
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return ExcelDate::PHPToExcel(Carbon::createFromFormat('m/d/Y', $value));
    }

    private function cleanWellName(string $value): string
    {
        if (!self::CLEAN_WELL_NAME) {
            return $value;
        }

        $value = preg_replace('/\s*-{2,}\s*/u', '-', trim($value));

        return trim((string) preg_replace('/\s*-\s*/u', '-', (string) $value));
    }
}

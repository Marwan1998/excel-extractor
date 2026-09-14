<?php

namespace App\Services\RunExtraction\Workover;

use App\Services\Pdf\NOCWellExtractorWorkover;
use App\Services\RunExtraction\ExpandsExcelTable;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class NOCExtractionDumpWorkover
{
    use ExpandsExcelTable;

    private const CLEAN_WELL_NAME = true;

    public function runExtraction(array $filesData): array
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $records = (new NOCWellExtractorWorkover())->extract($filePath);
            $workbookPath = storage_path('app/DWR.xlsx');
            $spreadsheet = IOFactory::load($workbookPath);
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = $sheet->getHighestRow() + 1;

            foreach ($records as $record) {
                $reportDate = $this->excelDate($record['report_date'] ?? null);
                $startOperation = $this->excelDate($record['start_operation'] ?? null);

                $sheet->setCellValue("A{$startRow}", $reportDate ?? '');
                $sheet->getStyle("A{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');
                $sheet->setCellValue("B{$startRow}", $record['company_name'] ?? '');
                $sheet->setCellValue("C{$startRow}", $record['report_no'] ?? '');
                $sheet->setCellValue("D{$startRow}", $record['field_name'] ?? '');
                $sheet->setCellValue("E{$startRow}", $this->cleanWellName($record['well_name'] ?? ''));
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');
                $sheet->setCellValue("H{$startRow}", $startOperation ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');
                $sheet->setCellValue("I{$startRow}", $record['budget'] ?? 0);
                $sheet->setCellValue("J{$startRow}", $record['cumulative_cost'] ?? 0);
                $sheet->setCellValue("K{$startRow}", $record['summary'] ?? '');
                $sheet->setCellValue("L{$startRow}", $record['days'] ?? '');
                $startRow++;
            }

            $this->expandExcelTableToRow($sheet, $startRow - 1, 12);
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

<?php

namespace App\Services\RunExtraction;

use App\Services\Pdf\NOCWellExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NOCExtractionDump
{
    use ExpandsExcelTable;
    use ResolvesWorkbookPath;

    private const CLEAN_WELL_NAME = true;

    public $excelDBFileStoragePathName;

    public function __construct()
    {
        $this->excelDBFileStoragePathName = (string) config(
            'report_automation.workbooks.drilling',
            'storage/app/DDR.xlsx'
        );
    }

    public function runExtraction(array $filesData): array
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $records = (new NOCWellExtractor())->extract($filePath);
            $workbookPath = $this->resolveWorkbookPathName($this->excelDBFileStoragePathName);
            $spreadsheet = IOFactory::load($workbookPath);
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = $sheet->getHighestRow() + 1;

            foreach ($records as $record) {
                $reportDateValue = $record['report_date'] ?? null;
                $reportDate = getExcelDateFormat($reportDateValue);
                $spudDate = getExcelDateFormat($record['spud_date'] ?? null);

                $sheet->setCellValue("A{$startRow}", $record['company_name'] ?? '');
                $sheet->setCellValue("B{$startRow}", $reportDate);
                $sheet->getStyle("B{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');
                $sheet->setCellValue("C{$startRow}", getDateId($reportDateValue));
                $sheet->setCellValue("D{$startRow}", $record['field_name'] ?? '');
                $sheet->setCellValue("E{$startRow}", cleanWellName($record['well_name'] ?? '', self::CLEAN_WELL_NAME));
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');
                $sheet->setCellValue("H{$startRow}", $spudDate);
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');
                $sheet->setCellValue("I{$startRow}", cleanNumericValue($record['target_depth'] ?? null, 0));
                $sheet->setCellValue("J{$startRow}", cleanNumericValue($record['progress'] ?? null, 0));
                $sheet->setCellValue("K{$startRow}", cleanNumericValue($record['current_depth'] ?? null, 0));
                $sheet->setCellValue("L{$startRow}", cleanNumericValue($record['budget'] ?? null, 0));
                $sheet->setCellValue("M{$startRow}", cleanNumericValue($record['cumulative_cost'] ?? null, 0));
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

}

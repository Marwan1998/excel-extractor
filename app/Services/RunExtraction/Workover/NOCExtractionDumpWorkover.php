<?php

namespace App\Services\RunExtraction\Workover;

use App\Services\Pdf\NOCWellExtractorWorkover;
use App\Services\RunExtraction\ExpandsExcelTable;
use App\Services\RunExtraction\ResolvesWorkbookPath;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NOCExtractionDumpWorkover
{
    use ExpandsExcelTable;
    use ResolvesWorkbookPath;

    private const CLEAN_WELL_NAME = true;

    public $excelDBFileStoragePathName;

    public function __construct()
    {
        $this->excelDBFileStoragePathName = (string) config(
            'report_automation.workbooks.workover',
            'storage/app/DWR.xlsx'
        );
    }

    public function runExtraction(array $filesData): array
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $records = (new NOCWellExtractorWorkover())->extract($filePath);
            $workbookPath = $this->resolveWorkbookPathName($this->excelDBFileStoragePathName);
            $spreadsheet = IOFactory::load($workbookPath);
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = $sheet->getHighestRow() + 1;

            foreach ($records as $record) {
                $reportDateValue = $record['report_date'] ?? null;
                $reportDate = getExcelDateFormat($reportDateValue);
                $startOperation = getExcelDateFormat($record['start_operation'] ?? null);

                $sheet->setCellValue("A{$startRow}", $reportDate);
                $sheet->getStyle("A{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');
                $sheet->setCellValue("B{$startRow}", $record['company_name'] ?? '');
                $sheet->setCellValue("C{$startRow}", getDateId($reportDateValue));
                $sheet->setCellValue("D{$startRow}", $record['field_name'] ?? '');
                $sheet->setCellValue("E{$startRow}", cleanWellName($record['well_name'] ?? '', self::CLEAN_WELL_NAME));
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');
                $sheet->setCellValue("H{$startRow}", $startOperation);
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');
                $sheet->setCellValue("I{$startRow}", cleanNumericValue($record['budget'] ?? null, 0));
                $sheet->setCellValue("J{$startRow}", cleanNumericValue($record['cumulative_cost'] ?? null, 0));
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

}

<?php

namespace App\Services\RunExtraction;

use App\Services\Pdf\AOOWellExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


class AOOExtractionDump extends ExtractionDump
{
    public function __construct()
    {
        $this->extractorClass = AOOWellExtractor::class;
        $this->companyName = 'AKAKUS Oil Operations';
        $this->excelDBFileStoragePathName = (string) config('report_automation.workbooks.drilling', 'storage/app/DDR.xlsx');
    }


    public function runExtraction($filesData)
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new $this->extractorClass();
            $data = $extractor->extract($filePath);

            // 2️⃣ Load DDR.xlsx
            $ddrPath = $this->resolveWorkbookPathName($this->excelDBFileStoragePathName);
            $spreadsheet = IOFactory::load($ddrPath);
            $sheet = $spreadsheet->getActiveSheet();

            // 3️⃣ Find last used row
            $startRow = $sheet->getHighestRow() + 1;

            // 4️⃣ Fixed values (edit freely)
            $companyName = $this->companyName;
            $reportDate = $this->getExcelDateFormat($value['date']);
            $reportNumber = getDateId($value['date']);

            // 5️⃣ Write rows
            foreach ($data as $record) {

                $targetDepth = $this->cleanNumericValue($record['target_depth'] ?? 0);
                $dailyFootage = $this->cleanNumericValue($record['progress'] ?? 0);
                $currentDepth = $this->cleanNumericValue($record['current_depth'] ?? 0);
                $cumCost = $this->cleanNumericValue($record['cumulative_cost'] ?? 0);


                $sheet->setCellValue("A{$startRow}", $companyName);
                $sheet->setCellValue("B{$startRow}", $reportDate);
                $sheet->getStyle("B{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');  

                $sheet->setCellValue("C{$startRow}", $reportNumber);
                $sheet->setCellValue("D{$startRow}", $record['field_name'] ?? '');

                $sheet->setCellValue("E{$startRow}", $record['well_name'] ?? '');
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');
                $sheet->setCellValue("H{$startRow}", $record['spud_date'] ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');  

                $sheet->setCellValue("I{$startRow}", $targetDepth ?? 0);
                $sheet->setCellValue("J{$startRow}", $dailyFootage ?? 0);
                $sheet->setCellValue("K{$startRow}", $currentDepth ?? 0);
                $sheet->setCellValue("L{$startRow}", $record['BUDGET'] ?? 0);
                $sheet->setCellValue("M{$startRow}", $cumCost ?? 0);

                $sheet->setCellValue("N{$startRow}", $record['summary'] ?? '');

                // TEMP
                $sheet->setCellValue("O{$startRow}", $record['report_no'] ?? '');

                $startRow++;
            }

            $this->expandExcelTableToRow($sheet, $startRow - 1, 14);

            // 6️⃣ Save back to DDR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($ddrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
            array_push($dataAdded, ['file-name' => $value['name'], 'count' => count($data), 'date' => $value['date']]);
        }

        \Log::info('Done inserting DDR successfully');

        return $dataAdded;
    }

    private function cleanNumericValue($value)
    {
        // Only reject real empties
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        // Extract first number (integer or decimal)
        if (preg_match('/\d+(\.\d+)?/', $value, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function getExcelDateFormat($dateValue)
    {
        if (empty($dateValue)) {
            return;
        }

        // Convert string / Carbon / DateTime → DateTime
        $date = \Carbon\Carbon::parse($dateValue);

        // Convert to Excel serial number
        return ExcelDate::PHPToExcel($date);
    }

}
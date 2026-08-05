<?php

namespace App\Services\RunExtraction;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;


class ExtractionDumpWorkover
{

    public $extractorClass;
    public $companyName;
    public $excelDBFileStoragePathName;

    public function __construct($extractorClass, $companyName, $excelDBFileStoragePathName)
    {
        logd($extractorClass);

        $this->extractorClass = $extractorClass;
        $this->companyName = $companyName;
        $this->excelDBFileStoragePathName = $excelDBFileStoragePathName;
    }


    public function runExtraction($filesData)
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new $this->extractorClass();
            $data = $extractor->extract($filePath);

            // 2️⃣ Load DWR.xlsx
            $dwrPath = storage_path($this->excelDBFileStoragePathName);
            $spreadsheet = IOFactory::load($dwrPath);
            $sheet = $spreadsheet->getActiveSheet();

            // 3️⃣ Find last used row
            $startRow = $sheet->getHighestRow() + 1;

            // 4️⃣ Fixed values (edit freely)
            $companyName = $this->companyName;
            $reportDate = $this->getExcelDateFormat($value['date']);
            $reportNumber = getDateId($value['date']);

            // 5️⃣ Write rows
            foreach ($data as $record) {

                $cumCost = cleanNumericValue($record['cumulative_cost'] ?? null);

                $wellName = $record['well_name'];
                $fieldName = $record['field_name'];

                $days = null;
                if (isset($record['operating_days'])) {
                    $days = $record['operating_days'];
                } elseif (isset($record['report_no'])) {
                    $days = $record['report_no'];
                } elseif (isset($record['days'])) {
                    $days = $record['days'];
                } else {
                    $days = null;
                }

                $buget = str_replace(',', '', $record['budget']??0);
                $buget = cleanNumericValue($buget);

                $sheet->setCellValue("A{$startRow}", $reportDate);
                $sheet->getStyle("A{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');  
                
                $sheet->setCellValue("B{$startRow}", $companyName);

                $sheet->setCellValue("C{$startRow}", $reportNumber);
                $sheet->setCellValue("D{$startRow}", $fieldName);

                $sheet->setCellValue("E{$startRow}", $wellName);
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');

                $sheet->setCellValue("H{$startRow}", $startOperation ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');  

                $sheet->setCellValue("I{$startRow}", $buget ?? 0);
                $sheet->setCellValue("J{$startRow}", $cumCost ?? 0);
                $sheet->setCellValue("K{$startRow}", $record['summary'] ?? '');

                $sheet->setCellValue("L{$startRow}", $days);

                $startRow++;
            }

            // 6️⃣ Save back to DWR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($dwrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
            array_push($dataAdded, ['file-name' => $value['name'], 'count' => count($data), 'date' => $value['date']]);
        }

        \Log::info('Done inserting DWR successfully');

        return $dataAdded;
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
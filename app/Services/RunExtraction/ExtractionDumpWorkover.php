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

                $cumCost = $this->cleanNumericValue($record['CUM.COST'] ?? null);

                $composedWellName = $this->splitWellName($record['WELL NAME'] ?? 'NO_DATA');
                [$well, $field] = $this->splitWellName($record['WELL NAME'] ?? 'NO_DATA');
                \Log::debug('Split well name', ['input' => $record['WELL NAME'] ?? 'NO_DATA', 'well_name' => $well, 'field_name' => $field]); //TODO: remove this TEMP log

                $wellName = $composedWellName[0];
                $fieldName = str_replace(' ', '', $composedWellName[1]);//to remove any spaces in the name

                $sheet->setCellValue("A{$startRow}", $reportDate);
                $sheet->getStyle("A{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');  
                
                $sheet->setCellValue("B{$startRow}", $companyName);

                $sheet->setCellValue("C{$startRow}", $reportNumber);
                $sheet->setCellValue("D{$startRow}", $fieldName);

                $sheet->setCellValue("E{$startRow}", $wellName);
                $sheet->setCellValue("F{$startRow}", $record['CONTR/RIG NO'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['OBJECTIVE'] ?? '');

                $sheet->setCellValue("H{$startRow}", $startOperation ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');  

                $sheet->setCellValue("I{$startRow}", $record['BUDGET'] ?? 0);
                $sheet->setCellValue("J{$startRow}", $cumCost ?? 0);
                $sheet->setCellValue("K{$startRow}", $record['SUMMARY'] ?? '');
                
                $sheet->setCellValue("L{$startRow}", $record['DAY'] ?? null);


                $startRow++;
            }

            // 6️⃣ Save back to DWR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($dwrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
            array_push($dataAdded, ['file-name' => $value['name'], 'count' => count($data), 'report-done' => $value]);
        }

        \Log::info('Done inserting DWR successfully');

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

    private function splitWellName(string $value): array
    {
        // Normalize spaces
        $value = trim(preg_replace('/\s+/', ' ', $value));

        // Normalize dots spacing: "S. ZELTEN" → "S.ZELTEN"
        $value = preg_replace('/\s*\.\s*/', '.', $value);

        // Detect well code at the beginning
        if (preg_match('/^([A-Z0-9]+[-][A-Z0-9\-]+)/i', $value, $match)) {
            $wellCode = $match[1];

            // Remove well code from string
            $remaining = trim(substr($value, strlen($wellCode)));

            return [
                $wellCode,
                $remaining ?: ''
            ];
        }

        // No well code → whole string is well name
        return [$value, ''];
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
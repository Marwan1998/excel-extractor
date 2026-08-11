<?php

namespace App\Services\RunExtraction;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;


class ExtractionDump
{
    use ExpandsExcelTable;

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

            // 2️⃣ Load DDR.xlsx
            $ddrPath = storage_path($this->excelDBFileStoragePathName);
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

                if(isset($record['DAY'])){
                    if(is_numeric($record['DAY'])){
                        $spudDate = $this->getExcelDateFormat($this->calculateSpudDate($value['date'], $record['DAY']));
                    } else {
                        $spudDate = '';
                    }
                } else {
                    $spudDate = $record['spud_date'] ?? null;
                }


                $dailyFootage = $this->cleanNumericValue($record['PROG'] ?? null);
                $cumCost = $this->cleanNumericValue($record['CUM.COST'] ?? null);
                $targetDepth = $this->cleanNumericValue($record['TD/TARGET'] ?? null);
                $currentDepth = $this->cleanNumericValue($record['CURRENT DEPTH'] ?? null);

                $composedWellName = $this->splitWellName($record['WELL NAME'] ?? 'NO_DATA');
                $wellName = $composedWellName[0];
                $fieldName = str_replace(' ', '', $composedWellName[1]);//to remove any spaces in the name

                $sheet->setCellValue("A{$startRow}", $companyName);
                $sheet->setCellValue("B{$startRow}", $reportDate);
                $sheet->getStyle("B{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');  

                $sheet->setCellValue("C{$startRow}", $reportNumber);
                $sheet->setCellValue("D{$startRow}", $fieldName);

                $sheet->setCellValue("E{$startRow}", $wellName);
                $sheet->setCellValue("F{$startRow}", $record['CONTR/RIG NO'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['OBJECTIVE'] ?? '');
                $sheet->setCellValue("H{$startRow}", $spudDate ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');  

                $sheet->setCellValue("I{$startRow}", $targetDepth ?? 0);
                $sheet->setCellValue("J{$startRow}", $dailyFootage ?? 0);
                $sheet->setCellValue("K{$startRow}", $currentDepth ?? 0);
                $sheet->setCellValue("L{$startRow}", $record['BUDGET'] ?? 0);
                $sheet->setCellValue("M{$startRow}", $cumCost ?? 0);

                $sheet->setCellValue("N{$startRow}", $record['SUMMARY'] ?? '');

                // TEMP
                $sheet->setCellValue("O{$startRow}", $record['DAY'] ?? '');

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

    private function splitWellName(string $value): array
    {
        $parts = preg_split('/\s+/', trim($value), 2);
        return [
            $parts[0] ?? $value,
            $parts[1] ?? null,
        ];
    }

    private function calculateSpudDate($date, $days)
    {
        if(empty($date) || empty($days) || $days == 0 || !is_numeric($days)){
            return '';
        }

        $date = Carbon::createFromFormat('m/d/Y', $date);

        // Subtract the desired number of days
        $newDate = $date->copy()->subDays($days)->format('m/d/Y');

        return $newDate; // Output: 22/12/2025
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
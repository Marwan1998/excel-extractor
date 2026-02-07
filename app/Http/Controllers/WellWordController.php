<?php

namespace App\Http\Controllers;

use App\Services\Word\WellWordExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WellWordController extends Controller
{
    public function run()
    {
        // $fileName = 'app/soc-01-2026.docx';
        // $filePath = storage_path($fileName);
        // $extractor = new WellWordExtractor();
        // $data = $extractor->extract($filePath);
        
        // \Log::debug('Data', [$fileName => count($data)]);

        $fileNames = [
            [
                'name' => 'app/soc-01-2026.docx',
                'date' => '01/01/2026',
                'number' => '366',
            ],
        ];

        $allData = [];
        foreach ($fileNames as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new WellWordExtractor();
            $data = $extractor->extract($filePath);

            array_push($allData, [count($data) => $data]);
            
            \Log::debug('Data', [$value['name'] => count($data)]);
        }
        return $allData;

        $this->runExtraction($fileNames);

        return 'Done';
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

    private function runExtraction($filesData)
    {
        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new WellWordExtractor();
            $data = $extractor->extract($filePath);

            // 2️⃣ Load DDR.xlsx
            $ddrPath = storage_path('app/DDR.xlsx');
            $spreadsheet = IOFactory::load($ddrPath);
            $sheet = $spreadsheet->getActiveSheet();

            // 3️⃣ Find last used row
            $startRow = $sheet->getHighestRow() + 1;

            // 4️⃣ Fixed values (edit freely)
            $companyName = 'Sirte Oil Company';
            $reportDate = $this->getExcelDateFormat($value['date']);
            $reportNumber = $value['number'];

            // 5️⃣ Write rows
            foreach ($data as $record) {

                if(is_numeric($record['DAY'])){
                    $spudDate = $this->getExcelDateFormat($this->calculateSpudDate($value['date'], $record['DAY']));
                } else {
                    $spudDate = '';
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

                $startRow++;
            }

            // 6️⃣ Save back to DDR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($ddrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
        }

        return 'DDR.xlsx updated successfully';
    }

    protected function splitWellName(string $value): array
    {
        $parts = preg_split('/\s+/', trim($value), 2);
        return [
            $parts[0] ?? $value,
            $parts[1] ?? null,
        ];
    }

    protected function calculateSpudDate($date, $days)
    {
        if(empty($date) || empty($days) || $days == 0 || !is_numeric($days)){
            return '';
        }

        $date = Carbon::createFromFormat('d/m/Y', $date);

        // Subtract the desired number of days
        $newDate = $date->subDays($days)->format('m/d/Y');

        return $newDate; // Output: 22/12/2025
    }

}

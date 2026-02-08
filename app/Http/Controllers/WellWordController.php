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
        $fileNames = [
            [
                'name' => 'app/JAN-01-2026.docx',
                'date' => '01/01/2026',
                'number' => '366',
            ],
            [
                'name' => 'app/JAN-02-2026.docx',
                'date' => '01/02/2026',
                'number' => '367',
            ],
            [
                'name' => 'app/JAN-03-2026.docx',
                'date' => '01/03/2026',
                'number' => '368',
            ],
            [
                'name' => 'app/JAN-04-2026.docx',
                'date' => '01/04/2026',
                'number' => '369',
            ],
            [
                'name' => 'app/JAN-05-2026.docx',
                'date' => '01/05/2026',
                'number' => '370',
            ],
            [
                'name' => 'app/JAN-06-2026.docx',
                'date' => '01/06/2026',
                'number' => '371',
            ],
            [
                'name' => 'app/JAN-07-2026.docx',
                'date' => '01/07/2026',
                'number' => '372',
            ],
            [
                'name' => 'app/JAN-08-2026.docx',
                'date' => '01/08/2026',
                'number' => '373',
            ],
            [
                'name' => 'app/JAN-09-2026.docx',
                'date' => '01/09/2026',
                'number' => '374',
            ],
            [
                'name' => 'app/JAN-10-2026.docx',
                'date' => '01/10/2026',
                'number' => '375',
            ],
            //
            [
                'name' => 'app/JAN-11-2026.docx',
                'date' => '01/11/2026',
                'number' => '376',
            ],
            [
                'name' => 'app/JAN-12-2026.docx',
                'date' => '01/12/2026',
                'number' => '377',
            ],
            [
                'name' => 'app/JAN-13-2026.docx',
                'date' => '01/13/2026',
                'number' => '378',
            ],
            [
                'name' => 'app/JAN-14-2026.docx',
                'date' => '01/14/2026',
                'number' => '379',
            ],
            [
                'name' => 'app/JAN-15-2026.docx',
                'date' => '01/15/2026',
                'number' => '380',
            ],
            [
                'name' => 'app/JAN-16-2026.docx',
                'date' => '01/16/2026',
                'number' => '381',
            ],
            [
                'name' => 'app/JAN-17-2026.docx',
                'date' => '01/17/2026',
                'number' => '382',
            ],
            [
                'name' => 'app/JAN-18-2026.docx',
                'date' => '01/18/2026',
                'number' => '383',
            ],
            [
                'name' => 'app/JAN-19-2026.docx',
                'date' => '01/19/2026',
                'number' => '384',
            ],
            [
                'name' => 'app/JAN-20-2026.docx',
                'date' => '01/20/2026',
                'number' => '385',
            ],
            //
            [
                'name' => 'app/JAN-21-2026.docx',
                'date' => '01/21/2026',
                'number' => '386',
            ],
            [
                'name' => 'app/JAN-22-2026.docx',
                'date' => '01/22/2026',
                'number' => '387',
            ],
            [
                'name' => 'app/JAN-23-2026.docx',
                'date' => '01/23/2026',
                'number' => '388',
            ],
            [
                'name' => 'app/JAN-24-2026.docx',
                'date' => '01/24/2026',
                'number' => '389',
            ],
            [
                'name' => 'app/JAN-25-2026.docx',
                'date' => '01/25/2026',
                'number' => '390',
            ],
            [
                'name' => 'app/JAN-26-2026.docx',
                'date' => '01/26/2026',
                'number' => '391',
            ],
            [
                'name' => 'app/JAN-27-2026.docx',
                'date' => '01/27/2026',
                'number' => '392',
            ],
            [
                'name' => 'app/JAN-28-2026.docx',
                'date' => '01/28/2026',
                'number' => '393',
            ],
            [
                'name' => 'app/JAN-29-2026.docx',
                'date' => '01/29/2026',
                'number' => '394',
            ],
            [
                'name' => 'app/JAN-30-2026.docx',
                'date' => '01/30/2026',
                'number' => '395',
            ],
            [
                'name' => 'app/JAN-31-2026.docx',
                'date' => '01/31/2026',
                'number' => '396',
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

        return $this->runExtraction($fileNames);

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
        $dataAdded = [];

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

                // TEMP
                $sheet->setCellValue("O{$startRow}", $record['DAY'] ?? '');

                $startRow++;
            }

            // 6️⃣ Save back to DDR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($ddrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
            array_push($dataAdded, ['file-name' => $value['name'], 'count' => count($data), 'report-done' => $value]);
        }

        return $dataAdded;
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

        \Log::debug('calculateSpudDate', ['date-in' => $date, 'days' => $days]);

        $date = Carbon::createFromFormat('m/d/Y', $date);

        \Log::debug('calculateSpudDate', ['date-out'=> $date, 'days-out' => $days]);

        // Subtract the desired number of days
        $newDate = $date->copy()->subDays($days)->format('m/d/Y');

        \Log::debug('calculateSpudDate', ['newDate'=> $newDate, 'days-in-result' => $days]);

        return $newDate; // Output: 22/12/2025
    }

}

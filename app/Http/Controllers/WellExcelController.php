<?php

namespace App\Http\Controllers;

use App\Services\Excel\WellExcelExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WellExcelController extends Controller
{
    public function test()
    {

        $fileNames = [
            [
                'name' => 'app/waha-report-1-1-2026.xlsx',
                'date' => '01/01/2026',
                'number' => '366',
            ],
            [
                'name' => 'app/waha-report-2-1-2026.xlsx',
                'date' => '01/02/2026',
                'number' => '367',
            ],
            [
                'name' => 'app/waha-report-3-1-2026.xlsx',
                'date' => '1/03/2026',
                'number' => '368',
            ],
            [
                'name' => 'app/waha-report-5-1-2026.xlsx',
                'date' => '01/05/2026',
                'number' => '370',
            ],
            [
                'name' => 'app/waha-report-6-1-2026.xlsx',
                'date' => '01/06/2026',
                'number' => '371',
            ],
            [
                'name' => 'app/waha-report-8-1-2026.xlsx',
                'date' => '01/08/2026',
                'number' => '373',
            ],

            [
                'name' => 'app/waha-report-9-1-2026.xlsx',
                'date' => '01/09/2026',
                'number' => '374',
            ],
            [
                'name' => 'app/waha-report-10-1-2026.xlsx',
                'date' => '01/10/2026',
                'number' => '375',
            ],
            [
                'name' => 'app/waha-report-13-1-2026.xlsx',
                'date' => '01/13/2026',
                'number' => '378',
            ],
            [
                'name' => 'app/waha-report-14-1-2026.xlsx',
                'date' => '01/14/2026',
                'number' => '379',
            ],
            [
                'name' => 'app/waha-report-15-1-2026.xlsx',
                'date' => '01/15/2026',
                'number' => '380',
            ],
            [
                'name' => 'app/waha-report-16-1-2026.xlsx',
                'date' => '01/16/2026',
                'number' => '381',
            ],
            [
                'name' => 'app/waha-report-18-1-2026.xlsx',
                'date' => '01/18/2026',
                'number' => '383',
            ],
            [
                'name' => 'app/waha-report-19-1-2026.xlsx',
                'date' => '01/19/2026',
                'number' => '384',
            ],

            //

            [
                'name' => 'app/waha-report-20-1-2026.xlsx',
                'date' => '01/20/2026',
                'number' => '385',
            ],
            [
                'name' => 'app/waha-report-22-1-2026.xlsx',
                'date' => '01/22/2026',
                'number' => '387',
            ],
            [
                'name' => 'app/waha-report-23-1-2026.xlsx',
                'date' => '01/23/2026',
                'number' => '388',
            ],
            [
                'name' => 'app/waha-report-24-1-2026.xlsx',
                'date' => '01/24/2026',
                'number' => '389',
            ],
            [
                'name' => 'app/waha-report-25-1-2026.xlsx',
                'date' => '01/25/2026',
                'number' => '390',
            ],
            [
                'name' => 'app/waha-report-27-1-2026.xlsx',
                'date' => '01/27/2026',
                'number' => '392',
            ],            
            [
                'name' => 'app/waha-report-28-1-2026.xlsx',
                'date' => '01/28/2026',
                'number' => '393',
            ],            
            [
                'name' => 'app/waha-report-29-1-2026.xlsx',
                'date' => '01/29/2026',
                'number' => '394',
            ],
            [
                'name' => 'app/waha-report-30-1-2026.xlsx',
                'date' => '01/30/2026',
                'number' => '395',
            ],
            [
                'name' => 'app/waha-report-31-1-2026.xlsx',
                'date' => '01/31/2026',
                'number' => '396',
            ],

        ];

        // $allData = [];
        // foreach ($fileNames as $value) {
        //     $filePath = storage_path($value['name']);
        //     $extractor = new WellExcelExtractor();
        //     $data = $extractor->extract($filePath);

        //     array_push($allData, [count($data) => $data]);
            
        //     \Log::debug('Data', [$value['name'] => count($data)]);
        // }

        // return $allData;



        $this->runExtraction($fileNames);


        //  ------------------------------------------------
    }


    private function cleanDateOnly($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Let Carbon parse anything it can
            return Carbon::parse($value)->format('n/j/Y');
        } catch (\Exception $e) {
            // Fallback: split by space
            return trim(explode(' ', $value)[0]);
        }
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
            $extractor = new WellExcelExtractor();
            $data = $extractor->extract($filePath);

            $fields = [
                'B239I-59W' => 'WAHA',
                'B234-59W' => 'WAHA',
                'A170-59W' => 'WAHA',
                'V63-59W' => 'BELHEDAN SITE',
                'F29-59W' => 'KHALIFA SITE',
                'B237-59W' => 'WAHA',
                'P37-59E' => 'MASRAB',
                'B225i-59W' => 'WAHA',
                'B241-59W' => 'N.DEFA',
                'B236-59W' => 'S.DEFA',
                'B233-59W' => 'S.DEFA',
                'B232i-59W' => 'S.DEFA',
                'Q124H-71' => 'WAHA',
                '6P4-59E' => 'HARASH',
                'B233A-59W' => 'NO_DATA',
                'B243H-59W' => 'NO_DATA',
                'B240-59W' => 'N.DEFA',
                'B235-59W' => 'WAHA',
            ];


            // 2️⃣ Load DDR.xlsx
            $ddrPath = storage_path('app/DDR.xlsx');
            $spreadsheet = IOFactory::load($ddrPath);
            $sheet = $spreadsheet->getActiveSheet();

            // 3️⃣ Find last used row
            $startRow = $sheet->getHighestRow() + 1;

            // 4️⃣ Fixed values (edit freely)
            $fixedTextA = 'Waha Oil Company';
            $fixedDateB = $this->getExcelDateFormat($value['date']);
            $fixedNumberC = $value['number'];

            // 5️⃣ Write rows
            foreach ($data as $record) {
                $spudDate = $this->getExcelDateFormat($this->cleanDateOnly($record['SPUD IN DATE'] ?? null));
                $dailyFootage = $this->cleanNumericValue($record['DAILY FOOTAGE'] ?? null);
                $cumCost = $this->cleanNumericValue($record['CUM COST'] ?? null);
                $targetDepth = $this->cleanNumericValue($record['TD/TARGET'] ?? null);
                $currentDepth = $this->cleanNumericValue($record['CURRENT DEPTH'] ?? null);

                $fixedTextD = $fields[$record['WELL NAME'] ?? null] ?? 'NO_DATA';

                $sheet->setCellValue("A{$startRow}", $fixedTextA);
                $sheet->setCellValue("B{$startRow}", $fixedDateB);
                $sheet->getStyle("B{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');  

                $sheet->setCellValue("C{$startRow}", $fixedNumberC);
                $sheet->setCellValue("D{$startRow}", $fixedTextD);

                $sheet->setCellValue("E{$startRow}", $record['WELL NAME'] ?? '');
                $sheet->setCellValue("F{$startRow}", $record['CONTR/RIG NO'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['OBJECTIVE'] ?? '');
                $sheet->setCellValue("H{$startRow}", $spudDate ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');  

                $sheet->setCellValue("I{$startRow}", $targetDepth ?? 0);
                $sheet->setCellValue("J{$startRow}", $dailyFootage ?? 0);
                $sheet->setCellValue("K{$startRow}", $currentDepth ?? 0);
                $sheet->setCellValue("L{$startRow}", $record['BUDGET'] ?? 0);
                $sheet->setCellValue("M{$startRow}", $cumCost ?? 0);

                $sheet->setCellValue("N{$startRow}", $record['24 HRS - SUMMARY'] ?? '');

                $startRow++;
            }

            // 6️⃣ Save back to DDR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($ddrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
        }

        return 'DDR.xlsx updated successfully';
    }

}

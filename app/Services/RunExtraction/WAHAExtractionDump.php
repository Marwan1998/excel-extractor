<?php

namespace App\Services\RunExtraction;

use App\Services\Excel\WAHAWellExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;
use Carbon\Carbon;

class WAHAExtractionDump extends ExtractionDump
{

    public function __construct()
    {
        $this->extractorClass = WAHAWellExtractor::class;
        $this->companyName = 'Waha Oil Company';
        $this->excelDBFileStoragePathName = 'app/DDR.xlsx';
    }

    public function runExtraction($filesData)
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new WAHAWellExtractor();
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
            $ddrPath = storage_path($this->excelDBFileStoragePathName);
            $spreadsheet = IOFactory::load($ddrPath);
            $sheet = $spreadsheet->getActiveSheet();

            // 3️⃣ Find last used row
            $startRow = $sheet->getHighestRow() + 1;

            // 4️⃣ Fixed values (edit freely)
            $fixedTextA = $this->companyName;
            $fixedDateB = getExcelDateFormat($value['date']);
            $fixedNumberC = getDateId($value['date']);

            // 5️⃣ Write rows
            foreach ($data as $record) {
                $spudDate = getExcelDateFormat($this->cleanDateOnly($record['SPUD IN DATE'] ?? null));
                $dailyFootage = cleanNumericValue($record['DAILY FOOTAGE'] ?? null);
                $cumCost = cleanNumericValue($record['CUM COST'] ?? null);
                $targetDepth = cleanNumericValue($record['TD/TARGET'] ?? null);
                $currentDepth = cleanNumericValue($record['CURRENT DEPTH'] ?? null);

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
            array_push($dataAdded, ['file-name' => $value['name'], 'count' => count($data), 'report-done' => $value]);
        }

        \Log::info('Done inserting DDR successfully');

        return $dataAdded;
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
}
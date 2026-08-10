<?php

namespace App\Services\RunExtraction;

use App\Services\Pdf\WAHAWellExtractorPDF;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;
use Carbon\Carbon;

class WAHAExtractionDumpPDF extends ExtractionDump
{

    public function __construct()
    {
        $this->extractorClass = WAHAWellExtractorPDF::class;
        $this->companyName = 'Waha Oil Company';
        $this->excelDBFileStoragePathName = 'app/DDR.xlsx';
    }

    public function runExtraction($filesData)
    {
        $dataAdded = [];

        foreach ($filesData as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new WAHAWellExtractorPDF();
            $data = $extractor->extract($filePath);

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
                $spudDate = getExcelDateFormat($this->cleanDateOnly($record['spud_date'] ?? null));
                $dailyFootage = cleanNumericValue($record['daily_footage'] ?? null);
                $cumCost = cleanNumericValue($record['cumulative_cost'] ?? null);
                $targetDepth = cleanNumericValue($record['td_target'] ?? null);
                $currentDepth = cleanNumericValue($record['current_depth'] ?? null);


                $sheet->setCellValue("A{$startRow}", $fixedTextA);
                $sheet->setCellValue("B{$startRow}", $fixedDateB);
                $sheet->getStyle("B{$startRow}")->getNumberFormat()->setFormatCode('d-mmm-yy');  

                $sheet->setCellValue("C{$startRow}", $fixedNumberC);
                $sheet->setCellValue("D{$startRow}", $record['field_name'] ?? '');

                $sheet->setCellValue("E{$startRow}", $record['well_name'] ?? '');
                $sheet->setCellValue("F{$startRow}", $record['rig_name'] ?? '');
                $sheet->setCellValue("G{$startRow}", $record['objective'] ?? '');

                $sheet->setCellValue("H{$startRow}", $spudDate ?? '');
                $sheet->getStyle("H{$startRow}")->getNumberFormat()->setFormatCode('mm/dd/yyyy');  

                $sheet->setCellValue("I{$startRow}", $targetDepth ?? 0);
                $sheet->setCellValue("J{$startRow}", $dailyFootage ?? 0);
                $sheet->setCellValue("K{$startRow}", $currentDepth ?? 0);
                $sheet->setCellValue("L{$startRow}", $record['budget'] ?? 0);
                $sheet->setCellValue("M{$startRow}", $cumCost ?? 0);
                $sheet->setCellValue("N{$startRow}", $record['summary'] ?? '');
                $sheet->setCellValue("O{$startRow}", $record['report_no'] ?? '');

                $startRow++;
            }

            // 6️⃣ Save back to DDR.xlsx
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($ddrPath);

            \Log::debug('Count', [$value['name'] => count($data), 'report-done' => $value]);
            array_push($dataAdded, ['file-name' => $value['name'], 'count' => count($data), 'date' => $value['date']]);
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
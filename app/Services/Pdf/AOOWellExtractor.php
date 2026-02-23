<?php

namespace App\Services\Pdf;

// use App\Services\Contracts\ReportExtractorInterface;
use Smalot\PdfParser\Parser;

class AOOWellExtractor 
{
    public function extract(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Normalize spacing
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        // Split wells
        $blocks = preg_split('/Well Name:/', $text);

        $records = [];

        foreach ($blocks as $block) {

            if (!str_contains($block, 'DATE:')) {
                continue;
            }

            logd($block);

            $record = [
                'date' => $this->match('/DATE:\s*([0-9A-Za-z\-\/]+)/', $block),
                'WELL NAME' => $this->match('/^(.*?)DATE:/', $block),
                'CONTR/RIG NO' => $this->match('/AFE:\s*([A-Z0-9\/\-]+)/', $block),
                'OBJECTIVE' => $this->match('/([A-Za-z]+)\s*OBJECTIVE:/', $block),
                'CURRENT DEPTH' => $this->cleanNumber(
                    $this->match('/END DATE\s*:\s*([\d,\.]+)/', $block)
                ),
                'PROG' => $this->cleanNumber(
                    // $this->match('/FOOTAGE\s*:\s*([\d,\.]+)/', $block)
                    $this->match('/FORMATION NAME\s*:\s*([\d,]+\.\d+)\s*DRILLING HOURS\s*:/', $block)

                ),
                'spud_date' => $this->match('/SPUD DATE\s*:\s*([0-9\/]+)/', $block),
                'CUM.COST' => $this->cleanNumber(
                    // $this->match('/CUM\. COST:\s*([\d,\.]+)/', $block),  
                    $this->match('/DAILY COST\s*:\s*([\d,]+\.\d+)/', $block) // for some how the cum_cost exist after the DAILY COST in the pdf code structure
                ),
                'SUMMARY' => trim(
                    $this->match('/Present Operations:\s*(.*?)\s*Oper\. Summary:/s', $block)
                ),
            ];

            $record['WELL NAME'] = trim($record['WELL NAME']);

            $records[] = $record;
        }


        return $records;
    }

    private function match($pattern, $text)
    {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function cleanNumber($value)
    {
        if (!$value) return null;
        return str_replace(',', '', $value);
    }
}

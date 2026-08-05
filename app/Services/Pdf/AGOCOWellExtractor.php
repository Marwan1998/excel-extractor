<?php

namespace App\Services\Pdf;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class AGOCOWellExtractor
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): array
    {
        Log::debug('PDF extractor started', ['file' => $filePath]);

        $pdf = $this->parser->parseFile($filePath);
        $lines = [];

        foreach ($pdf->getPages() as $page) {
            $text = str_replace("\r", "\n", $page->getText());
            foreach (explode("\n", $text) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        return $this->parseLines($lines);
    }

    protected function parseLines(array $lines): array
    {
        $records = [];
        $current = null;
        $inSummary = false;

        foreach ($lines as $line) {

            // 🧱 NEW WELL HEADER
            if ($this->isHeaderLine($line)) {

                if ($current) {
                    $records[] = $current;
                }

                $current = $this->parseHeaderLine($line);
                $inSummary = false;
                continue;
            }

            if (!$current) {
                continue;
            }

            // 💰 COSTS (also END summary)
            if (preg_match('/DC:\s*([\d,]+)/i', $line, $m)) {
                $current['DAILY COST'] = str_replace(',', '', $m[1]);
                $inSummary = false;
            }

            if (preg_match('/CC:\s*([\d,]+)/i', $line, $m)) {
                $current['CUM.COST'] = str_replace(',', '', $m[1]);
                // $current['CUM.COST'] = (int)cleanNumericValue($current['CUM.COST']);
                $current['CUM.COST'] = (float)$current['CUM.COST'];
            }

            // 🟢 SUMMARY START
            if (str_starts_with($line, 'YEST:')) {
                $inSummary = true;
                $line = preg_replace('/^YEST:\s*/', '', $line);
                $current['SUMMARY'] = trim($line);
                continue;
            }

            // 📝 SUMMARY CONTINUATION
            if ($inSummary) {
                // stop summary if DC line accidentally comes late
                if (preg_match('/^DC:/', $line)) {
                    $inSummary = false;
                    continue;
                }

                $current['SUMMARY'] .= ' ' . trim($line);
            }
        }

        if ($current) {
            $records[] = $current;
        }

        // foreach ($records as $record) {
        //     logd($record, 'before');

        //     $record['SUMMARY'] = json_encode($record['SUMMARY']);

        //     $record['CURRENT DEPTH'] = json_encode($record['CURRENT DEPTH']);
        //     $record['CUM.COST'] = json_encode($record['CUM.COST']);
        //     $record['DAILY COST'] = json_encode($record['DAILY COST']);

        //     $record['DAY'] = json_encode($record['DAY']);
        //     $record['CONTR/RIG NO'] = json_encode($record['CONTR/RIG NO']);
        //     $record['WELL NAME'] = json_encode($record['WELL NAME']);

        //     $record['TD/TARGET'] = json_encode($record['TD/TARGET']);
        //     $record['OBJECTIVEDAY'] = json_encode($record['OBJECTIVE']);
        //     $record['PROG'] = json_encode($record['PROG']);

        //     logd($record, 'aftere');
        // }

        return $records;
    }

    protected function isHeaderLine(string $line): bool
    {
        return preg_match(
            '/^[A-Z0-9\-]+\s+.+\s+DAY:\s*(RM\s*)?\(\d+\).+TD:/i',
            $line
        ) === 1;
    }

    protected function parseHeaderLine(string $line): array
    {
        $record = [];

        // WELL NAME
        preg_match('/^([A-Z0-9\-]+)/', $line, $m);
        $record['WELL NAME'] = $m[1];

        // RIG NAME
        if (preg_match('/^[A-Z0-9\-]+\s+(.*?)\s+DAY:/', $line, $m)) {
            $rigRaw = trim($m[1]);

            $rigRaw = preg_replace('/\s+/', ' ', trim($rigRaw));

            // SHM LY D2044 → SHMLY-D2044
            $rigRaw = preg_replace(
                '/^([A-Z]+)\s+([A-Z]+)\s+([A-Z]\d+)$/',
                '$1$2-$3',
                $rigRaw
            );

            $record['CONTR/RIG NO'] = $rigRaw;
        }

        // DAYS (normal + RM)
        if (preg_match('/DAY:\s*(?:RM\s*)?\((\d+)\)/', $line, $m)) {
            $record['DAY'] = $m[1];
        }

        // DEPTHS
        if (preg_match('/DEP:\s*\(([\d,]+)\'\)\s*\(([\d,]+)\'\)/', $line, $m)) {
            $record['CURRENT DEPTH'] = str_replace(',', '', $m[1]);
            $record['PROG'] = str_replace(',', '', $m[2]);
        } elseif (preg_match('/DEP:\s*\(([\d,]+)\'/', $line, $m)) {
            $record['CURRENT DEPTH'] = str_replace(',', '', $m[1]);
            $record['PROG'] = '0';
        }

        // TD
        if (preg_match('/TD:\s*([\d,]+)/', $line, $m)) {
            $record['TD/TARGET'] = str_replace(',', '', $m[1]);
        }

        // OBJECTIVE (OPER)
        if (preg_match('/OPER:\s*(.*?)(?:\s+TD:|$)/i', $line, $m)) {
            $record['OBJECTIVE'] = trim($m[1]) ?: null;
        } else {
            $record['OBJECTIVE'] = '';
        }


        return $record;
    }
}

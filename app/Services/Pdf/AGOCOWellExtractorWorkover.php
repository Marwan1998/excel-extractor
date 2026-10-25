<?php

namespace App\Services\Pdf;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class AGOCOWellExtractorWorkover
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): array
    {
        $pdf = $this->parser->parseFile($filePath);
        $text = $pdf->getText();
        try {
            $positionedRows = $this->positionedWellRigRows($pdf);
        } catch (\Throwable $exception) {
            Log::warning('AGOCO Workover PDF positions unavailable; using text extraction', [
                'file' => $filePath,
                'error' => $exception->getMessage(),
            ]);
            $positionedRows = [];
        }
        $positionedRowIndex = 0;

        $lines = preg_split('/\r\n|\r|\n/', $text);

        $results = [];

        $currentField = null;
        $currentRow = null;
        $collectSummary = false;
        $summaryBuffer = '';
        $pendingOperationType = false;

        foreach ($lines as $index => $line) {

            $line = trim($line);

            if (!$line) {
                continue;
            }

            // Log::debug("LINE {$index}: {$line}");

            // ---------------------------------
            // Detect Field (FIXED: save previous row before switching)
            // ---------------------------------
            if (preg_match('/^Field\s*(.+)$/i', $line, $m)) {

                if ($currentRow) {
                    $currentRow['summary'] = trim($summaryBuffer);

                    // clean summary
                    $currentRow['summary'] = $this->cleanSummary($currentRow['summary']);

                    if (!$currentRow['objective']) {
                        $currentRow['objective'] = 'EMPTY';
                    }

                    $results[] = $currentRow;

                    // Log::debug('Captured Row (before field switch)', $currentRow);

                    $currentRow = null;
                    $summaryBuffer = '';
                    $collectSummary = false;
                }

                $currentField = strtoupper(trim($m[1]));
                // Log::debug('Detected Field', ['field' => $currentField]);
                continue;
            }

            // ---------------------------------
            // Skip headers / noise
            // ---------------------------------
            if (
                str_contains($line, 'Well Name Rig Name') ||
                str_contains($line, 'Operating') ||
                str_contains($line, 'Moving') ||
                str_contains($line, 'Daily Cost') ||
                str_contains($line, 'Cumulative Cost') ||
                str_contains($line, 'Operation Type') ||
                preg_match('/^\d+\/\d+$/', $line)
            ) {
                continue;
            }

            // ---------------------------------
            // Detect Data Row (UPDATED regex)
            // ---------------------------------
            // ---------------------------------
            // Prefer the PDF's actual well/rig cells to text that may merge them.
            if (isset($positionedRows[$positionedRowIndex])
                && preg_match('/^(.+?)(\s+\d+\s+\d+(?:\s+\d+)?\s+[\d,]+\s+[\d,]+\s*[A-Z\s\-]*)$/', $line, $rowParts)
            ) {
                [$well, $rig] = $positionedRows[$positionedRowIndex];
                if ($this->compactRowIdentity($rowParts[1]) === $this->compactRowIdentity($well . $rig)) {
                    $line = $well . ' ' . $rig . $rowParts[2];
                }
            }

            // Retain text-only recovery when a positional match is unavailable.
            $line = preg_replace(
                '/^(\S+?)([A-Z]{2,}#\d+)(?=\s+\d+\s+\d+\s+\d+\s+[\d,]+\s+[\d,]+)/',
                '$1 $2',
                $line
            );

            if (preg_match(
                '/^(\S+)\s+(.+?)\s+(\d+)\s+(\d+)(?:\s+(\d+))?\s+([\d,]+)\s+([\d,]+)\s*([A-Z\s\-]*)$/',
                $line,
                $matches
            )) {
                $positionedRowIndex++;

                if ($currentRow) {
                    $currentRow['summary'] = $this->cleanSummary(trim($summaryBuffer));

                    if (!$currentRow['objective']) {
                        $currentRow['objective'] = 'EMPTY';
                    }

                    $results[] = $currentRow;
                }

                $summaryBuffer = '';
                $collectSummary = false;

                $operation = trim($matches[8] ?? '');

                $pendingOperationType = ($operation === '' || strlen($operation) < 3);

                $currentRow = [
                    'field_name'       => $currentField,
                    'well_name'        => trim($matches[1]),
                    'rig_name'         => trim($matches[2]),
                    'operating_days'   => (int) $matches[4],
                    'cumulative_cost'  => (int) str_replace(',', '', $matches[7]),
                    'objective'        => $operation ?: null,
                    'summary'          => null,
                ];


                // -------------------------------------------------------------------------------------------------------------------------------------------------------
                // Temporary: only extract specific wells
                // $targetWells = ['G1-NC2','G2-NC100'];
                // if (!in_array($currentRow['well_name'], $targetWells, true)) {
                //     $currentRow = null;
                //     $summaryBuffer = '';
                //     $collectSummary = false;
                //     $pendingOperationType = false;
                //     continue;
                // }
                // --------------------------------------------------------------------------------------------------------------------------------------------------------


                if ($currentRow['objective']) {
                    $currentRow['objective'] = str_replace('-', '', $currentRow['objective']);
                }

                continue;
            }

            // ---------------------------------
            // FIX: Capture multi-line operation type
            // ---------------------------------
            if (
                $pendingOperationType &&
                $currentRow &&
                preg_match('/^[A-Z ]+$/', $line) &&
                !str_contains($line, 'SUMMARY')
            ) {

                if (!$currentRow['objective']) {
                    $currentRow['objective'] = trim($line);
                } else {
                    $currentRow['objective'] .= ' ' . trim($line);
                    $pendingOperationType = false;
                }

                // ✅ ADD THIS (VERY IMPORTANT)
                $pendingOperationType = false;
            
                continue;
            }

            // ---------------------------------
            // Detect Summary Start
            // ---------------------------------
            if (str_contains($line, '12 HRs Summary')) {

                $collectSummary = true;

                $parts = explode('12 HRs Summary', $line);
                if (!empty(trim($parts[1]))) {
                    $summaryBuffer .= ' ' . trim($parts[1]);
                }

                continue;
            }

            // ---------------------------------
            // FIX: Stop Summary + SAVE ROW here
            // ---------------------------------
            if (str_contains($line, 'Next Operation')) {

                if ($currentRow) {
                    $currentRow['summary'] = trim($summaryBuffer);

                    // clean summary
                    $currentRow['summary'] = $this->cleanSummary($currentRow['summary']);

                    if (!$currentRow['objective']) {
                        $currentRow['objective'] = 'EMPTY';
                    }

                    $results[] = $currentRow;

                    // Log::debug('Captured Row (on Next Operation)', $currentRow);
                }

                $currentRow = null;
                $summaryBuffer = '';
                $collectSummary = false;

                continue;
            }

            if (str_contains($line, 'Initial Objective')) {
                $collectSummary = false;
                continue;
            }

            // ---------------------------------
            // Collect Summary Lines
            // ---------------------------------
            if ($collectSummary) {
                $summaryBuffer .= ' ' . $line;
                continue;
            }
        }

        // ---------------------------------
        // Save last row (safety)
        // ---------------------------------
        if ($currentRow) {
            $currentRow['summary'] = trim($summaryBuffer);
        
            // clean summary
            $currentRow['summary'] = $this->cleanSummary($currentRow['summary']);

            if (!$currentRow['objective']) {
                $currentRow['objective'] = 'EMPTY';
            }

            $results[] = $currentRow;

            // Log::debug('Captured Last Row', $currentRow);
        }

        return $results;
    }

    private function compactRowIdentity(string $value): string
    {
        return strtoupper(preg_replace('/\s+/u', '', $value));
    }

    /**
     * Read well and rig cells from their positions, irrespective of rig format.
     */
    private function positionedWellRigRows(\Smalot\PdfParser\Document $pdf): array
    {
        $rows = [];

        foreach ($pdf->getPages() as $page) {
            $lines = [];
            foreach ($page->getDataTm() as $item) {
                $value = trim($item[1] ?? '');
                if ($value === '') {
                    continue;
                }

                $matrix = $item[0];
                $y = sprintf('%.1f', (float) $matrix[5]);
                $lines[$y][] = [(float) $matrix[4], $value];
            }

            foreach ($lines as $cells) {
                usort($cells, static function (array $a, array $b): int {
                    return $a[0] <=> $b[0];
                });

                if (count($cells) < 4
                    || $cells[0][0] >= 50
                    || $cells[1][0] <= 50
                    || $cells[1][0] >= 180
                    || !preg_match('/^\d+$/', $cells[2][1])
                    || !preg_match('/^\d+$/', $cells[3][1])
                ) {
                    continue;
                }

                $rows[] = [$cells[0][1], $cells[1][1]];
            }
        }

        return $rows;
    }

    public function cleanSummary($summary)
    {
        $cleanSummary = $summary;

        $cleanSummary = preg_replace('/\s+/', ' ', $cleanSummary);

        return $cleanSummary;
    }
}

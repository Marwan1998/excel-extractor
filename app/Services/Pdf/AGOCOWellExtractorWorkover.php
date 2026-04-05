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
        Log::debug('AGOCO Workover Extractor Started', ['file' => $filePath]);

        $pdf = $this->parser->parseFile($filePath);
        $text = $pdf->getText();

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

                    if (!$currentRow['operation_type']) {
                        $currentRow['operation_type'] = 'EMPTY';
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
            if (preg_match(
                '/^(\S+)\s+(.+?)\s+(\d+)\s+(\d+)\s+(\d+)\s+([\d,]+)\s+([\d,]+)\s*([A-Z\s\-]*)$/',
                $line,
                $matches
            )) {

                // Save previous row
                if ($currentRow) {
                    $currentRow['summary'] = trim($summaryBuffer);

                    // clean summary
                    $currentRow['summary'] = $this->cleanSummary($currentRow['summary']);
                    
                    if (!$currentRow['operation_type']) {
                        $currentRow['operation_type'] = 'EMPTY';
                    }

                    $results[] = $currentRow;

                    // Log::debug('Captured Row', $currentRow);
                }

                $summaryBuffer = '';
                $collectSummary = false;

                $operation = trim($matches[8]);

                // FIX: handle missing operation type
                if ($operation === '' || strlen($operation) < 3) {
                    $pendingOperationType = true;
                } else {
                    $pendingOperationType = false;
                }

                $currentRow = [
                    'field_name'       => $currentField,
                    'well_name'        => trim($matches[1]),
                    'rig_name'         => trim($matches[2]),
                    'operating_days'   => (int)$matches[4],
                    'cumulative_cost'  => (int)str_replace(',', '', $matches[7]),
                    'operation_type'   => $operation ?: null,
                    'summary'          => null,
                ];

                // normalize
                if ($currentRow['operation_type']) {
                    $currentRow['operation_type'] = str_replace('-', '', $currentRow['operation_type']);
                }

                // Log::debug('Parsed Data Row', $currentRow);

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

                if (!$currentRow['operation_type']) {
                    $currentRow['operation_type'] = trim($line);
                } else {
                    $currentRow['operation_type'] .= ' ' . trim($line);
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

                    if (!$currentRow['operation_type']) {
                        $currentRow['operation_type'] = 'EMPTY';
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

            if (!$currentRow['operation_type']) {
                $currentRow['operation_type'] = 'EMPTY';
            }

            $results[] = $currentRow;

            // Log::debug('Captured Last Row', $currentRow);
        }

        return $results;
    }

    public function cleanSummary($summary)
    {
        $cleanSummary = $summary;

        $cleanSummary = preg_replace('/\s+/', ' ', $cleanSummary);
        $cleanSummary = preg_replace('/,\s*,/', ',', $cleanSummary);
        $cleanSummary = str_replace('"', '\"', $cleanSummary);

        return $cleanSummary;
    }
}
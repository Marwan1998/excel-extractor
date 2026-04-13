<?php

namespace App\Services\Pdf;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class WAHAWellExtractorWorkover
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

        // Normalize global text (keep line breaks!)
        $text = preg_replace('/\r/', '', $text);

        Log::debug('==== FULL TEXT SAMPLE ====');
        Log::debug(substr($text, 0, 1500));

        // Split into blocks (each well)
        preg_match_all('/[A-Z]+-\d+.*?(?=\n[A-Z]+-\d+|$)/s', $text, $blocks);

        Log::debug('Blocks found: ' . count($blocks[0]));

        $results = [];

        foreach ($blocks[0] as $i => $block) {

            Log::debug("---- BLOCK $i ----");
            Log::debug(substr($block, 0, 500));

            // ✅ KEEP RAW BLOCK (for SUMMARY)
            $rawBlock = $block;

            // ✅ Normalize ONLY for regex fields
            $block = trim($block);
            $block = preg_replace('/\n+/', "\n", $block);
            $block = preg_replace('/\s+/', ' ', $block);

            $row = [
                'field_name' => null,
                'well_name' => null,
                'rig_name' => null,
                'objective' => null,
                'budget' => null,
                'cumulative_cost' => null,
                'summary' => null,
            ];

            // =========================
            // FIELD NAME
            // =========================
            if (preg_match('/^([A-Z]+)-\d+/m', $block, $m)) {
                $row['field_name'] = $m[1];
            }

            // =========================
            // WELL NAME
            // =========================
            if (preg_match('/([A-Z0-9]+[A-Z0-9\-]*-\d+[A-Z]*)\s*Legal Well Name/i', $block, $m)) {
                $row['well_name'] = trim($m[1]);
            }

            // =========================
            // RIG NO
            // =========================
            if (preg_match('/REPORT No#\s*(.*?)\s*Contr\s*\/\s*Rig No/i', $block, $m)) {

                $rig = trim($m[1]);
                $rig = preg_replace('/\s+/', ' ', $rig);

                if (preg_match('/([A-Z]+)\s*\/\s*(\d+)/i', $rig, $r)) {
                    $row['rig_name'] = strtoupper($r[1]) . '/' . $r[2];
                } elseif (preg_match('/\/\d+/', $rig, $r)) {
                    $row['rig_name'] = $r[0];
                } else {
                    $row['rig_name'] = strtoupper($rig);
                }
            }

            // =========================
            // OBJECTIVE
            // =========================
            if (preg_match('/TD:(.*?)(?:DATE:|[A-Z ]+:)/s', $block, $m)) {

                $objective = $this->cleanText($m[1]);

                $objective = preg_replace('/\d{1,3}(?:,\d{3})*\s*\(ft\)/i', '', $objective);

                $row['objective'] = trim($objective);

                // some cleanings
                $row['objective'] = preg_replace('/\s*\.\s*\(ft\)$|\s*\(ft\)$|\s*\.$/', '.', $row['objective']);

                // $row['objective'] = \Illuminate\Support\Str::replaceLast(' . (ft)', '', $row['objective']);
                // $row['objective'] = \Illuminate\Support\Str::replaceLast(' (ft)', '', $row['objective']);
                // $row['objective'] = \Illuminate\Support\Str::replaceLast(' .', '.', $row['objective']);
            }

            // =========================
            // BUDGET
            // =========================
            if (preg_match('/Budget\s*:\s*\(\$\).*?Planned Days\s*:\s*\d+\s+(.*?)(?:OBJECTIVE|REPORT)/is', $block, $m)) {

                preg_match_all('/\d{1,3}(?:,\d{3})+/', $m[1], $nums);

                if (!empty($nums[0])) {
                    $row['budget'] = end($nums[0]);
                }
            }

            // =========================
            // CUM COST
            // =========================
            if (preg_match('/CUM\. COST:.*?\(\$\)\s*([\d,\.]+)/s', $block, $m)) {
                $row['cumulative_cost'] = str_replace(',', '', $m[1]);
            }

            // =========================
            // SUMMARY (FIXED)
            // =========================
            $row['summary'] = $this->extractSummary($rawBlock);

            $results[] = $row;
        }

        return $results;
    }

    // =========================
    // CLEAN TEXT
    // =========================
    private function cleanText($text)
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/,\s*,/', ',', $text);

        // OCR fixes
        $text = str_replace(['C0ONT', 'SAWB'], ['CONT', 'SWAB'], $text);

        $text = preg_replace('/Present Operation/i', '', $text);

        return trim($text);
    }

    // =========================
    // SUMMARY EXTRACTION (FINAL)
    // =========================
    private function extractSummary(string $block): string
    {
        // 1. Clean up weird spacing/tabs but keep newlines
        $block = str_replace("\t", " ", $block);

        // 2. Define the start and end anchors based on your examples.
        // We want the text AFTER "Present Operation" but BEFORE "SUMMARY :"
        // Using /s modifier to allow dot (.) to match newlines.
        $pattern = '/Present\s+Operation\s*(.*?)\s*SUMMARY\s*:/is';

        if (preg_match($pattern, $block, $matches)) {
            $summary = $matches[1];
        } else {
            // Fallback: If "SUMMARY :" is not found, try to capture between 
            // "Present Operation" and "FORECAST:"
            $fallbackPattern = '/Present\s+Operation\s*(.*?)\s*FORECAST\s*:/is';
            if (preg_match($fallbackPattern, $block, $matches)) {
                $summary = $matches[1];
            } else {
                $summary = '';
            }
        }

        // 3. Clean up the resulting string
        $summary = trim($summary);

        // Optional: Log the result for debugging
        // \Log::debug(['EXTRACTED_SUMMARY' => $summary]);

        return $summary;
    }
}
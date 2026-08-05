<?php

namespace App\Services\Pdf;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

/**
 * Extracts well report rows from an AKAKUS / NOC
 * "Daily Summary Report – Drilling Activities" PDF.
 *
 * Field mapping (from real smalot output):
 *   field_name       ← standalone group header line  (I&R / NC115 / NC186 …)
 *   well_name        ← between "Well Name:" and "DATE:"
 *   rig_name         ← after "AFE:" (smalot swaps Rig Name / AFE columns)
 *   objective        ← text BEFORE "OBJECTIVE:" on the same line
 *   budget           ← not present in this report layout (always null)
 *   cumulative_cost  ← after "DAILY COST :"  (NOT CUM. COST)
 *   summary          ← lines that follow "Present Operations:" up to "Oper. Summary:"
 */
class AOOWellExtractor
{
    protected Parser $parser;

    /** Flip to true to dump every raw smalot line to Log::debug(). */
    protected bool $debug = false;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    // ------------------------------------------------------------------ //
    //  PUBLIC ENTRY POINT
    // ------------------------------------------------------------------ //

    public function extract(string $filePath): array
    {
        Log::debug('[AOO] Extractor started', ['file' => $filePath]);

        $pdf   = $this->parser->parseFile($filePath);
        $lines = [];

        // Flatten all pages so cross-page wells are handled transparently.
        foreach ($pdf->getPages() as $page) {
            $text = str_replace(["\r\n", "\r"], "\n", $page->getText());
            foreach (explode("\n", $text) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        if ($this->debug) {
            foreach ($lines as $i => $line) {
                Log::debug(sprintf('[AOO][RAW] %04d | %s', $i, $line));
            }
        }

        $results = $this->parseLines($lines);

        Log::debug('[AOO] Extractor finished', ['rows' => count($results)]);

        return $results;
    }

    // ------------------------------------------------------------------ //
    //  CORE PARSER
    // ------------------------------------------------------------------ //

    protected function parseLines(array $lines): array
    {
        $results        = [];
        $currentField   = null;   // I&R / NC115 / NC186 …
        $currentRow     = null;   // row being built
        $collectSummary = false;
        $summaryBuffer  = '';

        foreach ($lines as $line) {

            // ── Skip layout noise ────────────────────────────────────────
            if ($this->isNoiseLine($line)) {
                continue;
            }

            // ── Field-group header: I&R / NC115 / NC186 … ───────────────
            if ($this->isFieldCodeLine($line)) {
                $currentField = $line;
                continue;
            }

            // ── Well header ──────────────────────────────────────────────
            // smalot format: "Well Name:R47i- I&RDATE:10-Jul-2026  Rig Name / NO.: {AFE}AFE:{RIG}"
            if ($this->isWellHeaderLine($line)) {

                // Save the previous well before starting a new one.
                if ($currentRow !== null) {
                    // The summary is finalized when "Oper. Summary:" is reached.
                    // At that point the buffer is cleared, so only flush here when
                    // it still contains data (for reports missing that end marker).
                    if ($summaryBuffer !== '') {
                        $currentRow['summary'] = $this->flushSummary($summaryBuffer);
                    }
                    $results[]             = $currentRow;
                }

                $summaryBuffer  = '';
                $collectSummary = false;
                $currentRow     = $this->parseWellHeader($line, $currentField);
                continue;
            }

            if ($currentRow === null) {
                continue;
            }

            // ── OBJECTIVE ────────────────────────────────────────────────
            // smalot merges value + label onto one line:
            // "VERTICAL WELLOBJECTIVE:   K.B: 1,619.00  (ft)"
            // The objective VALUE sits BEFORE "OBJECTIVE:".
            if (stripos($line, 'OBJECTIVE:') !== false && $currentRow['objective'] === null) {
                if (preg_match('/^(.+?)\s*OBJECTIVE\s*:/i', $line, $m)) {
                    $val = trim($m[1]);
                    $currentRow['objective'] = $val !== '' ? $val : null;
                }
                continue;
            }

            // Smalot reads these two-column rows in visual column order, so the
            // requested value appears at the end of each extracted text line.
            if (stripos($line, 'CURRENT DEPTH:') !== false && $currentRow['current_depth'] === null) {
                if (preg_match('/CURRENT\s+DEPTH\s*:.*?([\d,]+(?:\.\d+)?)\s*$/i', $line, $m)) {
                    $currentRow['current_depth'] = (float) str_replace(',', '', $m[1]);
                }
                continue;
            }

            if (stripos($line, 'FOOTAGE') !== false && $currentRow['progress'] === null) {
                if (preg_match('/FOOTAGE\s*:.*?([\d,]+(?:\.\d+)?)\s*$/i', $line, $m)) {
                    $currentRow['progress'] = (float) str_replace(',', '', $m[1]);
                }
                continue;
            }

            if (stripos($line, 'SPUD DATE') !== false && $currentRow['spud_date'] === null) {
                if (preg_match('/SPUD\s+DATE\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $line, $m)) {
                    $currentRow['spud_date'] = $m[1];
                }
                continue;
            }

            if (stripos($line, 'REPORT No:') !== false && $currentRow['report_no'] === null) {
                if (preg_match('/REPORT\s+No\s*:.*?(\d+)\s*$/i', $line, $m)) {
                    $currentRow['report_no'] = (int) $m[1];
                }
                continue;
            }

            // ── CUMULATIVE COST (= DAILY COST in this report) ────────────
            // Line: "DAILY COST : 4,670.00CUM. COST: 1,042.00  ($)  ($)"
            // The field we want is DAILY COST, not CUM. COST.
            if (stripos($line, 'DAILY COST') !== false && $currentRow['cumulative_cost'] === null) {
                if (preg_match('/DAILY\s+COST\s*:\s*([\d,]+(?:\.\d{1,2})?)/i', $line, $m)) {
                    $currentRow['cumulative_cost'] = (float) str_replace(',', '', $m[1]);
                }
                // No continue – let the line also fall through if needed.
            }

            // ── SUMMARY START: "Present Operations:" ─────────────────────
            // The text on this line is just a brief title (e.g. "Drilling 12 1/4" hole").
            // The real detail we want starts on the NEXT line.
            if (preg_match('/^Present\s+Operations?\s*:/i', $line)) {
                $collectSummary = true;
                $summaryBuffer  = '';   // discard the title on this line
                continue;
            }

            // ── SUMMARY END: "Oper. Summary:" ────────────────────────────
            // This line signals the end of the Present Operations body.
            // Everything after it (Oper. Summary text, Next Operations,
            // General Notes) is irrelevant for our output.
            if (preg_match('/^Oper\.?\s*Summary\s*:/i', $line)) {
                $currentRow['summary'] = $this->flushSummary($summaryBuffer);
                $collectSummary        = false;
                $summaryBuffer         = '';
                continue;
            }

            // ── Lines we never want in the summary ───────────────────────
            if (preg_match('/^(?:Next\s+Operations?|General\s+Notes|RIG\s+SUPERVISOR)\s*:/i', $line)) {
                $collectSummary = false;
                continue;
            }

            // ── SUMMARY BODY ─────────────────────────────────────────────
            if ($collectSummary) {
                $summaryBuffer .= ' ' . $line;
            }
        }

        // Flush the last well if it was never closed by "Oper. Summary:".
        if ($currentRow !== null) {
            if ($summaryBuffer !== '') {
                $currentRow['summary'] = $this->flushSummary($summaryBuffer);
            }
            $results[] = $currentRow;
        }

        return $results;
    }

    // ------------------------------------------------------------------ //
    //  LINE CLASSIFIERS
    // ------------------------------------------------------------------ //

    protected function isNoiseLine(string $line): bool
    {
        $patterns = [
            '/DRILLING\s*&\s*WORKOVER/i',
            '/AKAKUS\s+OIL\s+OPERATIONS/i',
            '/National\s+Oil\s+Corporation/i',
            '/Confidential\s+and\s+Proprietary/i',
            '/DAILY\s+SUMMARY\s+REPORT/i',
            '/DRILLING\s+ACTIVITIES/i',
            '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday),/i',
            '/^\d{4}-\d{2}-\d{2}Start\s+Date/i',   // "2026-07-10Start Date :"
            '/^Start\s+Date\s*:/i',
            '/^End\s+Date\s*:/i',
            '/^Page\s+\d+\s+of\s+\d+/i',
            '/^MUD\s/i',                             // "MUD  MUD TYPE  VISMUD WT"
            '/^DATA\b/i',                            // "DATA  (ppg)…" and "DATA 9.60(ppg)…"
            '/^\(ppg\)/i',
            '/^\(s\/qt\)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Short standalone uppercase tokens on their own line: I&R, NC115, NC186 …
     * Alphanumeric + & only, no spaces, max 10 chars.
     */
    protected function isFieldCodeLine(string $line): bool
    {
        if (!preg_match('/^[A-Z][A-Z0-9&]{1,9}$/i', $line)) {
            return false;
        }

        $noise = ['MUD', 'DATA', 'VIS', 'OK', 'WOC', 'BOP', 'ROP', 'TD', 'KB', 'GL', 'AFE'];

        return !in_array(strtoupper($line), $noise, true);
    }

    /**
     * smalot merges the table header into one line starting with "Well Name:".
     * str_starts_with() is PHP 8+, so we use strpos() for PHP 7 compatibility.
     */
    protected function isWellHeaderLine(string $line): bool
    {
        return strpos($line, 'Well Name:') === 0
            && stripos($line, 'DATE:') !== false;
    }

    // ------------------------------------------------------------------ //
    //  ROW BUILDER
    // ------------------------------------------------------------------ //

    protected function parseWellHeader(string $line, ?string $fieldCode): array
    {
        $row = [
            'field_name'      => $fieldCode,
            'well_name'       => null,
            'rig_name'        => null,
            'objective'       => null,
            'budget'          => null,
            'cumulative_cost' => null,
            'current_depth'   => null,
            'progress'        => null,
            'spud_date'       => null,
            'report_no'       => null,
            'summary'         => null,
        ];

        // Well Name: between "Well Name:" and "DATE:"
        if (preg_match('/Well\s+Name:\s*(.+?)\s*DATE:/i', $line, $m)) {
            $row['well_name'] = trim($m[1]) ?: null;
        }

        // Rig Name: smalot puts the AFE code after "Rig Name / NO.:" and
        // the actual rig name after "AFE:" — columns are swapped.
        if (preg_match('/AFE:\s*(.+?)\s*$/i', $line, $m)) {
            $row['rig_name'] = trim($m[1]) ?: null;
        }

        return $row;
    }

    protected function flushSummary(string $buffer): ?string
    {
        $summary = trim(preg_replace('/\s+/', ' ', $buffer));

        return $summary !== '' ? $summary : null;
    }
}
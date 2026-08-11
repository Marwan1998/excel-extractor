<?php

namespace App\Console\Commands;

use App\Services\ReportAutomation\ReportFileDiscovery;
use App\Services\ReportAutomation\ReportStatusStore;
use Illuminate\Console\Command;

class BaselineReports extends Command
{
    protected $signature = 'reports:baseline
                            {--confirm : Confirm that currently discovered files must not be imported}';

    protected $description = 'Mark all existing supported reports as already handled without importing them';

    public function handle(): int
    {
        if (!$this->option('confirm')) {
            $this->error('No files were changed. Re-run with --confirm to baseline existing reports.');

            return 1;
        }

        $store = new ReportStatusStore();
        $reports = (new ReportFileDiscovery())->discover();
        $added = 0;
        $existing = 0;

        foreach ($reports as $report) {
            if ($store->find($report['fingerprint']) !== null) {
                $existing++;
                continue;
            }

            $store->record($report['fingerprint'], ReportStatusStore::STATUS_BASELINED, [
                'path' => $report['path'],
                'relative_path' => $report['relative_path'],
                'report_type' => $report['report_type'],
                'company' => $report['company'],
                'report_date' => $report['report_date'],
                'size' => $report['size'],
                'modified_at' => $report['modified_at'],
                'attempts' => 0,
            ]);
            $added++;
        }

        $store->appendEvent('baseline_completed', [
            'baselined' => $added,
            'already_recorded' => $existing,
        ]);

        $this->info("Baseline complete: {$added} file(s) added, {$existing} already recorded.");

        return 0;
    }
}

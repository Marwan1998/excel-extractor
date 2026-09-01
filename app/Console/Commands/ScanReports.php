<?php

namespace App\Console\Commands;

use App\Services\ReportAutomation\ReportFileDiscovery;
use App\Services\ReportAutomation\ReportProcessor;
use App\Services\ReportAutomation\ReportStatusStore;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ScanReports extends Command
{
    protected $signature = 'reports:scan
                            {--dry-run : Discover files without processing them}
                            {--limit=0 : Maximum number of discovered files to inspect}
                            {--exclude= : Exclude one status from per-file terminal output}
                            {--force : Run even when automation is disabled}';

    protected $description = 'Discover and process new drilling and workover reports';

    public function handle(): int
    {
        if (!config('report_automation.enabled') && !$this->option('force')) {
            $this->info('Report automation is disabled. Set REPORT_AUTOMATION_ENABLED=true to enable it.');

            return 0;
        }

        $statusStore = new ReportStatusStore();
        $lock = $this->acquireRunLock();

        if ($lock === null) {
            $this->info('Another report automation scan is already running.');

            return 0;
        }

        try {
            $statusStore->appendEvent('scan_started', ['dry_run' => (bool) $this->option('dry-run')]);
            $reports = (new ReportFileDiscovery())->discover();
            $limit = max(0, (int) $this->option('limit'));

            if ($limit > 0) {
                $reports = array_slice($reports, 0, $limit);
            }

            if ($this->option('dry-run')) {
                $this->displayDiscoveredReports($reports);
                $statusStore->appendEvent('scan_completed', [
                    'dry_run' => true,
                    'discovered' => count($reports),
                ]);

                return 0;
            }

            $processor = new ReportProcessor(null, $statusStore);
            $runSummary = [];
            $excludedStatus = strtolower(trim((string) $this->option('exclude')));

            foreach ($reports as $report) {
                $result = $processor->process($report);
                $status = $result['status'] ?? 'unknown';
                $runSummary[$status] = ($runSummary[$status] ?? 0) + 1;


                if ($excludedStatus === '' || strtolower($status) !== $excludedStatus) {
                    $this->line(sprintf(
                        '[%s] %s',
                        strtoupper($status),
                        $report['relative_path']
                    ));
                }
            }

            ksort($runSummary);


            $statusStore->appendEvent('scan_completed', [
                'dry_run' => false,
                'discovered' => count($reports),
                'summary' => $runSummary,
            ]);

            $this->displaySummary($runSummary, count($reports));

            return ($runSummary[ReportStatusStore::STATUS_FAILED] ?? 0) > 0 ? 1 : 0;
        } catch (Throwable $exception) {
            $statusStore->appendEvent('scan_failed', ['error' => $exception->getMessage()]);
            $this->error($exception->getMessage());

            return 1;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function acquireRunLock()
    {
        $directory = (string) config('report_automation.state.lock_directory');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create the automation lock directory: {$directory}");
        }

        $handle = fopen($directory.'/scan.lock', 'c+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the report automation run lock.');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    protected function displayDiscoveredReports(array $reports): void
    {
        $rows = [];

        foreach ($reports as $report) {
            $rows[] = [
                $report['report_type'],
                $report['company'],
                $report['report_date'] ?? 'UNKNOWN',
                $report['stable'] ? 'yes' : 'no',
                $report['relative_path'],
            ];
        }

        $this->table(['Type', 'Company', 'Date', 'Stable', 'File'], $rows);
        $this->info('Discovered '.count($reports).' supported report file(s).');
    }

    protected function displaySummary(array $summary, int $discovered): void
    {
        $rows = [];

        foreach ($summary as $status => $count) {
            $rows[] = [$status, $count];
        }

        $this->table(['Status', 'Count'], $rows);
        $this->info("Scan finished. {$discovered} supported report file(s) inspected.");
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ReportAutomation\ReportStatusStore;
use Illuminate\Console\Command;

class ReportAutomationStatus extends Command
{
    protected $signature = 'reports:status
                            {--status= : Show only one status}
                            {--limit=50 : Maximum number of recent records}';

    protected $description = 'Show report automation processing status from the JSON ledger';

    public function handle(): int
    {
        $store = new ReportStatusStore();
        $records = $store->all();
        $filter = strtolower(trim((string) $this->option('status')));

        if ($filter !== '') {
            $records = array_values(array_filter($records, static function (array $record) use ($filter): bool {
                return ($record['status'] ?? '') === $filter;
            }));
        }

        usort($records, static function (array $left, array $right): int {
            return strcmp($right['updated_at'] ?? '', $left['updated_at'] ?? '');
        });

        $limit = max(0, (int) $this->option('limit'));
        if ($limit > 0) {
            $records = array_slice($records, 0, $limit);
        }

        $summary = $store->summary();
        $summaryRows = [];
        foreach ($summary as $status => $count) {
            $summaryRows[] = [$status, $count];
        }

        $this->table(['Status', 'Total'], $summaryRows);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                $record['updated_at'] ?? '',
                $record['status'] ?? '',
                $record['report_type'] ?? '',
                $record['company'] ?? '',
                $record['report_date'] ?? '',
                $record['record_count'] ?? '',
                $record['relative_path'] ?? $record['path'] ?? '',
                $record['error'] ?? '',
            ];
        }

        $this->table(
            ['Updated', 'Status', 'Type', 'Company', 'Date', 'Rows', 'File', 'Error'],
            $rows
        );

        return 0;
    }
}

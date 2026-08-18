<?php

namespace App\Console\Commands;

use App\Services\ReportAutomation\ReportStatusStore;
use Illuminate\Console\Command;

class ReportAutomationStatus extends Command
{
    protected $signature = 'reports:status
                            {--status= : Show only one status}
                            {--limits=50 : Maximum number of recent records}
                            {--days=0 : Show records from the latest available report dates}';

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

        $days = max(0, (int) $this->option('days'));
        if ($days > 0) {
            $availableDates = [];

            foreach ($records as $record) {
                $timestamp = strtotime((string) ($record['report_date'] ?? ''));

                if ($timestamp !== false) {
                    $availableDates[date('Y-m-d', $timestamp)] = true;
                }
            }

            $availableDates = array_keys($availableDates);
            rsort($availableDates);
            $includedDates = array_flip(array_slice($availableDates, 0, $days));

            $records = array_values(array_filter($records, static function (array $record) use ($includedDates): bool {
                $timestamp = strtotime((string) ($record['report_date'] ?? ''));

                return $timestamp !== false && isset($includedDates[date('Y-m-d', $timestamp)]);
            }));
        }

        usort($records, static function (array $left, array $right) use ($days): int {
            if ($days > 0) {
                $leftDate = strtotime((string) ($left['report_date'] ?? '')) ?: 0;
                $rightDate = strtotime((string) ($right['report_date'] ?? '')) ?: 0;
                $dateComparison = $rightDate <=> $leftDate;

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                $typeComparison = strcmp(
                    (string) ($right['report_type'] ?? ''),
                    (string) ($left['report_type'] ?? '')
                );

                if ($typeComparison !== 0) {
                    return $typeComparison;
                }
            }

            return strcmp($right['updated_at'] ?? '', $left['updated_at'] ?? '');
        });

        $limit = max(0, (int) $this->option('limits'));
        if ($limit > 0) {
            $records = array_slice($records, 0, $limit);
        }

        $records = array_reverse($records);

        $summary = $store->summary();
        $summaryRows = [];
        foreach ($summary as $status => $count) {
            $summaryRows[] = [$status, $count];
        }

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

        $this->table(['Status', 'Count'], $summaryRows);

        return 0;
    }
}

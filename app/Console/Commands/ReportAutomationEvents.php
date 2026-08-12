<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class ReportAutomationEvents extends Command
{
    protected $signature = 'reports:events
                            {--limits=20 : Number of latest events to show}';

    protected $description = 'Show the latest report automation events';

    public function handle(): int
    {
        $limitOption = $this->option('limits');

        if (!is_numeric($limitOption) || (int) $limitOption < 1) {
            $this->error('The --limits option must be a positive number.');

            return 1;
        }

        $limit = (int) $limitOption;
        $eventLog = (string) config('report_automation.state.event_log_file');

        if ($eventLog === '' || !is_file($eventLog)) {
            $this->info('No report automation events have been recorded yet.');

            return 0;
        }

        $lines = file($eventLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            $this->error("Unable to read the report automation event log: {$eventLog}");

            return 1;
        }

        $rows = [];

        foreach (array_slice($lines, -$limit) as $line) {
            $event = json_decode($line, true);

            if (!is_array($event)) {
                $rows[] = ['', 'invalid_json', $line];
                continue;
            }

            $context = $event['context'] ?? [];
            $context = $this->formatContext($context);
            $contextText = is_array($context)
                ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (string) $context;

            $rows[] = [
                $this->formatTimestamp($event['timestamp'] ?? null),
                $event['event'] ?? '',
                $contextText === false ? '' : $contextText,
            ];
        }

        if ($rows === []) {
            $this->info('No report automation events have been recorded yet.');

            return 0;
        }

        $this->table(['Timestamp', 'Event', 'Context'], $rows);
        $this->line('Showing '.count($rows).' latest event(s) from '.$this->compactPath($eventLog));

        return 0;
    }

    protected function formatTimestamp($timestamp): string
    {
        if (!is_string($timestamp) || trim($timestamp) === '') {
            return '';
        }

        try {
            return Carbon::parse($timestamp)->format('m/d/Y H:i');
        } catch (\Throwable $exception) {
            return $timestamp;
        }
    }

    protected function formatContext($value, string $key = '')
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $childKey = (string) $childKey;

                if (in_array($childKey, [
                    'relative_path',
                    'modified_at',
                    'fingerprint',
                    'updated_at',
                ], true)) {
                    unset($value[$childKey]);
                    continue;
                }

                $value[$childKey] = $this->formatContext($childValue, $childKey);
            }

            return $value;
        }

        if (is_string($value)) {
            if (preg_match('/(?:^date$|_date$|_at$)/i', $key)) {
                return $this->formatTimestamp($value);
            }

            if (preg_match('/(?:path|file|workbook|backup)/i', $key)) {
                return $this->compactPath($value);
            }
        }

        return $value;
    }

    protected function compactPath(string $path): string
    {
        return strlen($path) > 100
            ? substr($path, 0, 50).'.....'.substr($path, -10)
            : $path;
    }
}

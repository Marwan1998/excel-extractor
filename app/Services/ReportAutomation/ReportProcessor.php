<?php

namespace App\Services\ReportAutomation;

use RuntimeException;
use Throwable;

class ReportProcessor
{
    protected ReportPipelineRegistry $pipelines;
    protected ReportStatusStore $statusStore;
    protected array $state;
    protected int $maximumAttempts;
    protected int $maximumBackups;

    public function __construct(
        ?ReportPipelineRegistry $pipelines = null,
        ?ReportStatusStore $statusStore = null,
        ?array $state = null,
        ?int $maximumAttempts = null,
        ?int $maximumBackups = null
    ) {
        $this->pipelines = $pipelines ?? new ReportPipelineRegistry();
        $this->statusStore = $statusStore ?? new ReportStatusStore();
        $this->state = $state ?? config('report_automation.state', []);
        $this->maximumAttempts = $maximumAttempts
            ?? (int) config('report_automation.scan.maximum_attempts', 3);
        $this->maximumBackups = $maximumBackups
            ?? (int) config('report_automation.scan.maximum_backups_per_workbook', 20);

        foreach (['staging_directory', 'working_directory', 'backup_directory', 'lock_directory'] as $directory) {
            $this->ensureDirectory((string) ($this->state[$directory] ?? ''));
        }
    }

    public function process(array $report): array
    {
        $this->validateReport($report);
        $fingerprint = $report['fingerprint'];
        $existing = $this->statusStore->find($fingerprint);

        if ($this->statusStore->isHandled($fingerprint)) {
            return array_merge($existing, ['skipped' => true]);
        }

        $attempts = (int) ($existing['attempts'] ?? 0);

        if (($existing['status'] ?? null) === ReportStatusStore::STATUS_FAILED && $attempts >= $this->maximumAttempts) {
            return array_merge($existing, ['skipped' => true, 'retry_limit_reached' => true]);
        }

        if (!$report['stable']) {
            return $this->statusStore->record(
                $fingerprint,
                ReportStatusStore::STATUS_WAITING,
                $this->statusDetails($report, $attempts)
            );
        }

        if ($report['report_date'] === null) {
            return $this->statusStore->record(
                $fingerprint,
                ReportStatusStore::STATUS_FAILED,
                $this->statusDetails($report, $attempts + 1, [
                    'error' => 'The report date could not be extracted from the file name.',
                ])
            );
        }

        $attempts++;
        $stagedReport = null;
        $workingWorkbook = null;

        $this->statusStore->record(
            $fingerprint,
            ReportStatusStore::STATUS_PROCESSING,
            $this->statusDetails($report, $attempts, ['error' => null])
        );

        try {
            $pipeline = $this->pipelines->resolve($report['report_type'], $report['company']);
            $stagedReport = $this->stageReport($report);
            $extractorClass = $pipeline['extractor'];
            $extractor = new $extractorClass();
            $data = $extractor->extract($stagedReport);

            if (!is_array($data)) {
                throw new RuntimeException("The extractor did not return an array: {$extractorClass}");
            }

            if ($data === []) {
                return $this->statusStore->record(
                    $fingerprint,
                    ReportStatusStore::STATUS_EMPTY,
                    $this->statusDetails($report, $attempts, ['record_count' => 0])
                );
            }

            $dumpClass = $pipeline['dump'];
            $dump = new $dumpClass();
            $workbookRelativePath = $dump->excelDBFileStoragePathName ?? null;

            if (!is_string($workbookRelativePath) || $workbookRelativePath === '') {
                throw new RuntimeException("The dump class does not define an output workbook: {$dumpClass}");
            }

            $targetWorkbook = storage_path($workbookRelativePath);

            if (!is_file($targetWorkbook)) {
                throw new RuntimeException("The output workbook does not exist: {$targetWorkbook}");
            }

            return $this->withWorkbookLock($targetWorkbook, function () use (
                $dump,
                $data,
                $fingerprint,
                $report,
                $attempts,
                $stagedReport,
                $targetWorkbook,
                &$workingWorkbook
            ): array {
                $workingWorkbook = $this->createWorkingWorkbook($targetWorkbook, $fingerprint);
                $dump->excelDBFileStoragePathName = $this->storageRelativePath($workingWorkbook);
                $dumpResult = $dump->runExtraction([[
                    'name' => $this->storageRelativePath($stagedReport),
                    'date' => $report['report_date'],
                ]]);
                $dumpCount = $this->dumpRecordCount($dumpResult);

                if ($dumpCount !== count($data)) {
                    throw new RuntimeException(
                        'Extractor/dump record count mismatch: extracted '.count($data).", dumped {$dumpCount}."
                    );
                }

                $backup = $this->backupWorkbook($targetWorkbook, $fingerprint);
                $this->replaceWorkbook($workingWorkbook, $targetWorkbook);
                $workingWorkbook = null;
                $this->pruneBackups($targetWorkbook);

                return $this->statusStore->record(
                    $fingerprint,
                    ReportStatusStore::STATUS_COMPLETED,
                    $this->statusDetails($report, $attempts, [
                        'record_count' => $dumpCount,
                        'workbook' => $targetWorkbook,
                        'backup' => $backup,
                        'completed_at' => gmdate('c'),
                    ])
                );
            });
        } catch (Throwable $exception) {
            return $this->statusStore->record(
                $fingerprint,
                ReportStatusStore::STATUS_FAILED,
                $this->statusDetails($report, $attempts, [
                    'error' => $exception->getMessage(),
                    'failed_at' => gmdate('c'),
                ])
            );
        } finally {
            $this->deleteIfExists($stagedReport);
            $this->deleteIfExists($workingWorkbook);
        }
    }

    protected function stageReport(array $report): string
    {
        $extension = $report['extension'] !== '' ? '.'.$report['extension'] : '';
        $destination = rtrim($this->state['staging_directory'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$report['fingerprint'].$extension;

        if (!copy($report['path'], $destination)) {
            throw new RuntimeException("Unable to stage the report: {$report['path']}");
        }

        return $destination;
    }

    protected function createWorkingWorkbook(string $targetWorkbook, string $fingerprint): string
    {
        $workingWorkbook = rtrim($this->state['working_directory'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$fingerprint.'-'.basename($targetWorkbook);

        if (!copy($targetWorkbook, $workingWorkbook)) {
            throw new RuntimeException("Unable to create a working copy of: {$targetWorkbook}");
        }

        return $workingWorkbook;
    }

    protected function backupWorkbook(string $targetWorkbook, string $fingerprint): string
    {
        $backup = rtrim($this->state['backup_directory'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.pathinfo($targetWorkbook, PATHINFO_FILENAME)
            .'-'.gmdate('Ymd-His').'-'.substr($fingerprint, 0, 8).'.xlsx';

        if (!copy($targetWorkbook, $backup)) {
            throw new RuntimeException("Unable to back up the workbook: {$targetWorkbook}");
        }

        return $backup;
    }

    protected function replaceWorkbook(string $workingWorkbook, string $targetWorkbook): void
    {
        if (!rename($workingWorkbook, $targetWorkbook)) {
            throw new RuntimeException(
                "Unable to replace {$targetWorkbook}. It may currently be open in Excel."
            );
        }
    }

    protected function pruneBackups(string $targetWorkbook): void
    {
        if ($this->maximumBackups < 1) {
            return;
        }

        $pattern = rtrim($this->state['backup_directory'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.pathinfo($targetWorkbook, PATHINFO_FILENAME).'-*.xlsx';
        $backups = glob($pattern) ?: [];
        usort($backups, static function (string $left, string $right): int {
            return filemtime($right) <=> filemtime($left);
        });

        foreach (array_slice($backups, $this->maximumBackups) as $backup) {
            @unlink($backup);
        }
    }

    protected function dumpRecordCount($dumpResult): int
    {
        if (!is_array($dumpResult)) {
            throw new RuntimeException('The dump class did not return an array result.');
        }

        $count = 0;

        foreach ($dumpResult as $fileResult) {
            $count += (int) ($fileResult['count'] ?? 0);
        }

        return $count;
    }

    protected function withWorkbookLock(string $targetWorkbook, callable $callback)
    {
        $lockFile = rtrim($this->state['lock_directory'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.hash('sha256', $targetWorkbook).'.lock';
        $handle = fopen($lockFile, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to open the workbook lock: {$lockFile}");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException("Unable to lock the workbook: {$targetWorkbook}");
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    protected function storageRelativePath(string $absolutePath): string
    {
        $storageRoot = rtrim(storage_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (strpos($absolutePath, $storageRoot) !== 0) {
            throw new RuntimeException("Automation working files must be inside Laravel storage: {$absolutePath}");
        }

        return substr($absolutePath, strlen($storageRoot));
    }

    protected function statusDetails(array $report, int $attempts, array $extra = []): array
    {
        return array_merge([
            'path' => $report['path'],
            'relative_path' => $report['relative_path'],
            'report_type' => $report['report_type'],
            'company' => $report['company'],
            'report_date' => $report['report_date'],
            'size' => $report['size'],
            'modified_at' => $report['modified_at'],
            'attempts' => $attempts,
        ], $extra);
    }

    protected function validateReport(array $report): void
    {
        foreach ([
            'fingerprint', 'path', 'relative_path', 'report_type', 'company', 'extension',
            'report_date', 'size', 'modified_at', 'stable',
        ] as $key) {
            if (!array_key_exists($key, $report)) {
                throw new RuntimeException("The discovered report is missing the {$key} value.");
            }
        }
    }

    protected function ensureDirectory(string $directory): void
    {
        if ($directory === '') {
            throw new RuntimeException('A report automation state directory is not configured.');
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create the report automation directory: {$directory}");
        }
    }

    protected function deleteIfExists(?string $path): void
    {
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }
}

<?php

namespace App\Services\ReportAutomation;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

class ReportStatusStore
{
    public const STATUS_DISCOVERED = 'discovered';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EMPTY = 'empty';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BASELINED = 'baselined';

    protected string $ledgerFile;
    protected string $eventLogFile;
    protected string $lockFile;

    public function __construct(?string $ledgerFile = null, ?string $eventLogFile = null)
    {
        $this->ledgerFile = $ledgerFile ?? (string) config('report_automation.state.ledger_file');
        $this->eventLogFile = $eventLogFile ?? (string) config('report_automation.state.event_log_file');
        $this->lockFile = $this->ledgerFile.'.lock';

        $this->ensureDirectory(dirname($this->ledgerFile));
        $this->ensureDirectory(dirname($this->eventLogFile));
    }

    public function record(string $fingerprint, string $status, array $details = []): array
    {
        $this->assertStatus($status);

        return $this->withExclusiveLock(function () use ($fingerprint, $status, $details): array {
            $ledger = $this->readLedgerFile();
            $existing = $ledger['files'][$fingerprint] ?? [];
            $record = array_merge($existing, $details, [
                'fingerprint' => $fingerprint,
                'status' => $status,
                'updated_at' => gmdate('c'),
            ]);

            if (!isset($record['created_at'])) {
                $record['created_at'] = $record['updated_at'];
            }

            $ledger['files'][$fingerprint] = $record;
            $this->writeLedgerFile($ledger);
            $this->appendEventUnlocked('status_changed', $record);

            return $record;
        });
    }

    public function find(string $fingerprint): ?array
    {
        return $this->withSharedLock(function () use ($fingerprint): ?array {
            $ledger = $this->readLedgerFile();

            return $ledger['files'][$fingerprint] ?? null;
        });
    }

    public function isHandled(string $fingerprint): bool
    {
        $record = $this->find($fingerprint);

        return in_array($record['status'] ?? null, [
            self::STATUS_COMPLETED,
            self::STATUS_EMPTY,
            self::STATUS_BASELINED,
        ], true);
    }

    public function all(): array
    {
        return $this->withSharedLock(function (): array {
            return array_values($this->readLedgerFile()['files']);
        });
    }

    public function summary(): array
    {
        $summary = [];

        foreach ($this->all() as $record) {
            $status = $record['status'] ?? 'unknown';
            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        ksort($summary);

        return $summary;
    }

    public function appendEvent(string $event, array $context = []): void
    {
        $this->withExclusiveLock(function () use ($event, $context): void {
            $this->appendEventUnlocked($event, $context);
        });
    }

    protected function readLedgerFile(): array
    {
        if (!is_file($this->ledgerFile)) {
            return ['version' => 1, 'files' => []];
        }

        $contents = file_get_contents($this->ledgerFile);

        if ($contents === false || trim($contents) === '') {
            return ['version' => 1, 'files' => []];
        }

        try {
            $ledger = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "The report automation status file is not valid JSON: {$this->ledgerFile}",
                0,
                $exception
            );
        }

        if (!is_array($ledger) || !isset($ledger['files']) || !is_array($ledger['files'])) {
            throw new RuntimeException("The report automation status file has an invalid structure: {$this->ledgerFile}");
        }

        return $ledger;
    }

    protected function writeLedgerFile(array $ledger): void
    {
        try {
            $json = json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode the report automation status file.', 0, $exception);
        }

        $temporaryFile = $this->ledgerFile.'.tmp.'.getmypid();

        if (file_put_contents($temporaryFile, $json.PHP_EOL) === false) {
            throw new RuntimeException("Unable to write the temporary status file: {$temporaryFile}");
        }

        if (!rename($temporaryFile, $this->ledgerFile)) {
            @unlink($temporaryFile);
            throw new RuntimeException("Unable to replace the status file: {$this->ledgerFile}");
        }
    }

    protected function appendEventUnlocked(string $event, array $context): void
    {
        try {
            $line = json_encode([
                'timestamp' => gmdate('c'),
                'event' => $event,
                'context' => $context,
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode the report automation event.', 0, $exception);
        }

        if (file_put_contents($this->eventLogFile, $line.PHP_EOL, FILE_APPEND) === false) {
            throw new RuntimeException("Unable to append to the event log: {$this->eventLogFile}");
        }
    }

    protected function withExclusiveLock(callable $callback)
    {
        return $this->withLock(LOCK_EX, $callback);
    }

    protected function withSharedLock(callable $callback)
    {
        return $this->withLock(LOCK_SH, $callback);
    }

    protected function withLock(int $operation, callable $callback)
    {
        $handle = fopen($this->lockFile, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to open the automation lock file: {$this->lockFile}");
        }

        try {
            if (!flock($handle, $operation)) {
                throw new RuntimeException("Unable to lock the automation status store: {$this->lockFile}");
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    protected function assertStatus(string $status): void
    {
        if (!in_array($status, [
            self::STATUS_DISCOVERED,
            self::STATUS_WAITING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_EMPTY,
            self::STATUS_FAILED,
            self::STATUS_BASELINED,
        ], true)) {
            throw new InvalidArgumentException("Unknown report automation status: {$status}");
        }
    }

    protected function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create the report automation directory: {$directory}");
        }
    }
}

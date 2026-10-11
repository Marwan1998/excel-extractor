<?php

namespace App\Services\ReportAutomation;

use DateTimeInterface;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class ReportFileDiscovery
{
    protected ReportPipelineRegistry $pipelines;
    protected ReportDateParser $dateParser;
    protected array $roots;
    protected int $stabilitySeconds;
    protected bool $manualOnly;
    protected ?string $minimumMonth;

    public function __construct(
        ?ReportPipelineRegistry $pipelines = null,
        ?ReportDateParser $dateParser = null,
        ?array $roots = null,
        ?int $stabilitySeconds = null,
        bool $manualOnly = false,
        ?DateTimeInterface $minimumMonth = null
    ) {
        $this->pipelines = $pipelines ?? new ReportPipelineRegistry();
        $this->dateParser = $dateParser ?? new ReportDateParser();
        $this->roots = $roots ?? config('report_automation.roots', []);
        $this->stabilitySeconds = $stabilitySeconds
            ?? (int) config('report_automation.scan.stability_seconds', 120);
        $this->manualOnly = $manualOnly;
        $this->minimumMonth = $minimumMonth !== null ? $minimumMonth->format('Y-m') : null;
    }

    public function discover(): array
    {
        $reports = [];
        $configuredRoots = 0;

        foreach (['drilling', 'workover'] as $reportType) {
            $root = trim((string) ($this->roots[$reportType] ?? ''));

            if ($root === '') {
                continue;
            }

            $configuredRoots++;

            if (!is_dir($root)) {
                throw new RuntimeException("The configured {$reportType} report directory does not exist: {$root}");
            }

            foreach ($this->filesIn($root) as $file) {
                $report = $this->describeFile($reportType, $root, $file);

                if ($report !== null) {
                    $reports[] = $report;
                }
            }
        }

        if ($configuredRoots === 0) {
            throw new RuntimeException('No drilling or workover report root has been configured.');
        }

        usort($reports, static function (array $left, array $right): int {
            return strcmp($left['path'], $right['path']);
        });

        return $reports;
    }

    protected function filesIn(string $root): RecursiveIteratorIterator
    {
        $directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
        $filtered = new RecursiveCallbackFilterIterator(
            $directory,
            function (SplFileInfo $item) use ($root): bool {
                return !$item->isDir() || !$this->isExcludedMonthDirectory($root, $item->getPathname());
            }
        );

        return new RecursiveIteratorIterator(
            $filtered,
            RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    protected function isExcludedMonthDirectory(string $root, string $directory): bool
    {
        if ($this->minimumMonth === null) {
            return false;
        }

        $relativePath = ltrim(
            substr($directory, strlen(rtrim($root, DIRECTORY_SEPARATOR))),
            DIRECTORY_SEPARATOR
        );
        $parts = preg_split('~[\\/]~', $relativePath);

        if (!is_array($parts) || count($parts) < 2) {
            return false;
        }

        $cutoffYear = (int) substr($this->minimumMonth, 0, 4);
        $yearFolder = (string) ($parts[1] ?? '');

        if (count($parts) === 2 && preg_match('/^\d{4}$/', $yearFolder)) {
            return (int) $yearFolder < $cutoffYear;
        }

        if (count($parts) !== 3) {
            return false;
        }

        $folderMonth = $this->parseMonthFolder((string) $parts[2]);

        return $folderMonth !== null && strcmp($folderMonth, $this->minimumMonth) < 0;
    }

    protected function parseMonthFolder(string $folder): ?string
    {
        if (!preg_match('/^([A-Za-z]+)[\s_-]+(\d{4})$/', trim($folder), $matches)) {
            return null;
        }

        $months = [
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'aug' => 8,
            'sep' => 9,
            'oct' => 10,
            'nov' => 11,
            'dec' => 12,
        ];
        $month = $months[strtolower(substr($matches[1], 0, 3))] ?? null;

        if ($month === null) {
            return null;
        }

        return sprintf('%04d-%02d', (int) $matches[2], $month);
    }

    protected function describeFile(string $reportType, string $root, SplFileInfo $file): ?array
    {
        if (!$file->isFile() || $this->isTemporaryFile($file->getFilename())) {
            return null;
        }

        $relativePath = ltrim(substr($file->getPathname(), strlen(rtrim($root, DIRECTORY_SEPARATOR))), DIRECTORY_SEPARATOR);
        $parts = preg_split('~[\\\\/]~', $relativePath);
        $companyFolder = strtoupper((string) ($parts[0] ?? ''));

        try {
            $pipeline = $this->pipelines->resolve($reportType, $companyFolder);
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        if ((bool) ($pipeline['manual_only'] ?? false) !== $this->manualOnly) {
            return null;
        }

        if (!$this->pipelines->supportsFile($reportType, $companyFolder, $file->getPathname())) {
            return null;
        }

        $size = $file->getSize();
        $modifiedAt = $file->getMTime();
        $path = $file->getRealPath() ?: $file->getPathname();
        $fingerprint = hash('sha256', implode('|', [
            $reportType,
            $pipeline['company'],
            $path,
            $modifiedAt,
        ]));

        return [
            'fingerprint' => $fingerprint,
            'path' => $path,
            'relative_path' => str_replace('\\', '/', $relativePath),
            'report_type' => $reportType,
            'company' => $pipeline['company'],
            'extension' => strtolower($file->getExtension()),
            'report_date' => $this->dateParser->parse($file->getFilename()),
            'size' => $size,
            'modified_at' => gmdate('c', $modifiedAt),
            'stable' => $size > 0 && (time() - $modifiedAt) >= $this->stabilitySeconds,
        ];
    }

    protected function isTemporaryFile(string $fileName): bool
    {
        $lowerName = strtolower($fileName);

        return strpos($fileName, '~$') === 0
            || strpos($fileName, '.') === 0
            || substr($lowerName, -4) === '.tmp'
            || substr($lowerName, -5) === '.part'
            || substr($lowerName, -11) === '.crdownload';
    }
}

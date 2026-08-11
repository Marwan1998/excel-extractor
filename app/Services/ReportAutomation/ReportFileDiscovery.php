<?php

namespace App\Services\ReportAutomation;

use FilesystemIterator;
use InvalidArgumentException;
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

    public function __construct(
        ?ReportPipelineRegistry $pipelines = null,
        ?ReportDateParser $dateParser = null,
        ?array $roots = null,
        ?int $stabilitySeconds = null
    ) {
        $this->pipelines = $pipelines ?? new ReportPipelineRegistry();
        $this->dateParser = $dateParser ?? new ReportDateParser();
        $this->roots = $roots ?? config('report_automation.roots', []);
        $this->stabilitySeconds = $stabilitySeconds
            ?? (int) config('report_automation.scan.stability_seconds', 120);
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
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
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
            $size,
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

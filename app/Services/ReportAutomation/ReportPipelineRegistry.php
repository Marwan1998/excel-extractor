<?php

namespace App\Services\ReportAutomation;

use InvalidArgumentException;
use RuntimeException;

class ReportPipelineRegistry
{
    protected array $configuration;

    public function __construct(?array $configuration = null)
    {
        $this->configuration = $configuration ?? config('report_automation', []);
    }

    public function resolve(string $reportType, string $companyFolder): array
    {
        $reportType = strtolower(trim($reportType));
        $company = strtoupper(trim($companyFolder));
        $company = $this->configuration['company_aliases'][$company] ?? $company;
        $pipeline = $this->configuration['pipelines'][$reportType][$company] ?? null;

        if (!is_array($pipeline)) {
            throw new InvalidArgumentException(
                "No report automation pipeline is configured for {$reportType}/{$company}."
            );
        }

        foreach (['extractor', 'dump'] as $classKey) {
            $className = $pipeline[$classKey] ?? null;

            if (!is_string($className) || !class_exists($className)) {
                throw new RuntimeException(
                    "The configured {$classKey} class for {$reportType}/{$company} does not exist."
                );
            }
        }

        return array_merge($pipeline, [
            'report_type' => $reportType,
            'company' => $company,
        ]);
    }

    public function supportsFile(string $reportType, string $companyFolder, string $filePath): bool
    {
        $pipeline = $this->resolve($reportType, $companyFolder);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $extensions = array_map('strtolower', $pipeline['extensions'] ?? []);

        return in_array($extension, $extensions, true);
    }

    public function all(): array
    {
        return $this->configuration['pipelines'] ?? [];
    }
}

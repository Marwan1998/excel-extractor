<?php

use App\Services\Pdf\AGOCOWellExtractor;
use App\Services\Pdf\AGOCOWellExtractorWorkover;
use App\Services\Pdf\AOOWellExtractor;
use App\Services\Pdf\AOOWellExtractorWorkover;
use App\Services\Pdf\WAHAWellExtractorPDF;
use App\Services\Pdf\WAHAWellExtractorWorkover;
use App\Services\RunExtraction\AGOCOExtractionDump;
use App\Services\RunExtraction\AOOExtractionDump;
use App\Services\RunExtraction\SOCExtractionDump;
use App\Services\RunExtraction\WAHAExtractionDumpPDF;
use App\Services\RunExtraction\Workover\AGOCOExtractionDumpWorkover;
use App\Services\RunExtraction\Workover\AOOExtractionDumpWorkover;
use App\Services\RunExtraction\Workover\SOCExtractionDumpWorkover;
use App\Services\RunExtraction\Workover\WAHAExtractionDumpWorkover;
use App\Services\Word\SOCWellExtractor;
use App\Services\Word\SOCWellExtractorWorkover;

return [
    'enabled' => (bool) env('REPORT_AUTOMATION_ENABLED', false),

    // These must be WSL paths, for example /mnt/c/Users/.../Drilling Reports Per Company.
    'roots' => [
        'drilling' => env('REPORT_AUTOMATION_DRILLING_ROOT', ''),
        'workover' => env('REPORT_AUTOMATION_WORKOVER_ROOT', ''),
    ],

    'workbooks' => [
        'drilling' => env('REPORT_AUTOMATION_DDR_PATH', 'storage/app/DDR.xlsx'),
        'workover' => env('REPORT_AUTOMATION_DWR_PATH', 'storage/app/DWR.xlsx'),
    ],

    'state' => [
        'directory' => storage_path('app/report-automation'),
        'ledger_file' => storage_path('app/report-automation/status.json'),
        'event_log_file' => env('REPORT_AUTOMATION_EVENT_LOG_PATH', storage_path('app/report-automation/events.log')),
        'staging_directory' => storage_path('app/report-automation/staging'),
        'working_directory' => storage_path('app/report-automation/working'),
        'backup_directory' => storage_path('app/report-automation/backups'),
        'lock_directory' => storage_path('app/report-automation/locks'),
    ],

    'scan' => [
        'stability_seconds' => (int) env('REPORT_AUTOMATION_STABILITY_SECONDS', 120),
        'maximum_attempts' => (int) env('REPORT_AUTOMATION_MAXIMUM_ATTEMPTS', 3),
        'maximum_backups_per_workbook' => (int) env('REPORT_AUTOMATION_MAXIMUM_BACKUPS', 20),
    ],

    'company_aliases' => [
        'WAHA' => 'WOC',
    ],

    'pipelines' => [
        'drilling' => [
            'AGOCO' => [
                'extractor' => AGOCOWellExtractor::class,
                'dump' => AGOCOExtractionDump::class,
                'extensions' => ['pdf'],
            ],
            'AOO' => [
                'extractor' => AOOWellExtractor::class,
                'dump' => AOOExtractionDump::class,
                'extensions' => ['pdf'],
            ],
            'SOC' => [
                'extractor' => SOCWellExtractor::class,
                'dump' => SOCExtractionDump::class,
                'extensions' => ['docx'],
            ],
            'WOC' => [
                'extractor' => WAHAWellExtractorPDF::class,
                'dump' => WAHAExtractionDumpPDF::class,
                'extensions' => ['pdf'],
            ],
        ],

        'workover' => [
            'AGOCO' => [
                'extractor' => AGOCOWellExtractorWorkover::class,
                'dump' => AGOCOExtractionDumpWorkover::class,
                'extensions' => ['pdf'],
            ],
            'AOO' => [
                'extractor' => AOOWellExtractorWorkover::class,
                'dump' => AOOExtractionDumpWorkover::class,
                'extensions' => ['pdf'],
            ],
            'SOC' => [
                'extractor' => SOCWellExtractorWorkover::class,
                'dump' => SOCExtractionDumpWorkover::class,
                'extensions' => ['docx'],
            ],
            'WOC' => [
                'extractor' => WAHAWellExtractorWorkover::class,
                'dump' => WAHAExtractionDumpWorkover::class,
                'extensions' => ['pdf'],
            ],
        ],
    ],
];

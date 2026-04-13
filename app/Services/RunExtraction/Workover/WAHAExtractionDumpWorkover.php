<?php

namespace App\Services\RunExtraction\Workover;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;
use App\Services\Pdf\WAHAWellExtractorWorkover;
use App\Services\RunExtraction\ExtractionDumpWorkover;

class WAHAExtractionDumpWorkover extends ExtractionDumpWorkover
{
    public function __construct()
    {
        $this->extractorClass = WAHAWellExtractorWorkover::class;
        $this->companyName = 'WAHA Oil Company';
        $this->excelDBFileStoragePathName = 'app/DWR.xlsx';
    }
}
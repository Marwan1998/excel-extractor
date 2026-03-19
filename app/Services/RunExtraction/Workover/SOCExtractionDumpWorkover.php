<?php

namespace App\Services\RunExtraction\Workover;

use App\Services\Word\SOCWellExtractorWorkover;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;
use App\Services\RunExtraction\ExtractionDumpWorkover;

class SOCExtractionDumpWorkover extends ExtractionDumpWorkover
{
    public function __construct()
    {
        $this->extractorClass = SOCWellExtractorWorkover::class;
        $this->companyName = 'Sirte Oil Company';
        $this->excelDBFileStoragePathName = 'app/DWR.xlsx';
    }
}
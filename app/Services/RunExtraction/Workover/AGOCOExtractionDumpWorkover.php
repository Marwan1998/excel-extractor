<?php

namespace App\Services\RunExtraction\Workover;

use App\Services\Pdf\AGOCOWellExtractorWorkover;
use App\Services\RunExtraction\ExtractionDumpWorkover_NEW;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;


class AGOCOExtractionDumpWorkover extends ExtractionDumpWorkover_NEW
{
    public function __construct()
    {
        $this->extractorClass = AGOCOWellExtractorWorkover::class;
        $this->companyName = 'AGOCO';
        $this->excelDBFileStoragePathName = 'app/DWR.xlsx';
    }

}
<?php

namespace App\Services\RunExtraction\Workover;

use App\Services\Pdf\AOOWellExtractorWorkover;
use App\Services\RunExtraction\ExtractionDumpWorkover;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;


class AOOExtractionDumpWorkover extends ExtractionDumpWorkover
{
    public function __construct()
    {
        $this->extractorClass = AOOWellExtractorWorkover::class;
        $this->companyName = 'AKAKUS Oil Operations';
        $this->excelDBFileStoragePathName = (string) config('report_automation.workbooks.workover', 'storage/app/DWR.xlsx');
    }



}
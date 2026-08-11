<?php

namespace App\Services\RunExtraction;

use App\Services\Word\SOCWellExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;


class SOCExtractionDump extends ExtractionDump
{
    public function __construct()
    {
        $this->extractorClass = SOCWellExtractor::class;
        $this->companyName = 'Sirte Oil Company';
        $this->excelDBFileStoragePathName = (string) config('report_automation.workbooks.drilling', 'storage/app/DDR.xlsx');
    }
}
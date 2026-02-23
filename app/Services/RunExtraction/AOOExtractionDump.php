<?php

namespace App\Services\RunExtraction;

use App\Services\Pdf\AOOWellExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;


class AOOExtractionDump extends ExtractionDump
{
    public function __construct()
    {
        $this->extractorClass = AOOWellExtractor::class;
        $this->companyName = 'Akakus Oil Operation';
        $this->excelDBFileStoragePathName = 'app/DDR.xlsx';
    }
}
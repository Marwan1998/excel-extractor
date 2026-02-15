<?php

namespace App\Services\RunExtraction;

use App\Services\Pdf\AGOCOWellExtractor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\RunExtraction\ExtractionDump;

class AGOCOExtractionDump extends ExtractionDump
{
    public function __construct()
    {
        $this->extractorClass = AGOCOWellExtractor::class;
        $this->companyName = 'AGOCO';
        $this->excelDBFileStoragePathName = 'app/DDR.xlsx';
    }
}
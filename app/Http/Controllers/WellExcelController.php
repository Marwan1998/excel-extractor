<?php

namespace App\Http\Controllers;

use App\Services\Excel\WellExcelExtractor;

class WellExcelController extends Controller
{
    public function test()
    {
        $filePath = storage_path('app/waha-report.xlsx');

        $extractor = new WellExcelExtractor();
        $data = $extractor->extract($filePath);

        return ($data);
    }
}

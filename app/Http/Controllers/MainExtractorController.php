<?php

namespace App\Http\Controllers;

use App\Services\Excel\WAHAWellExtractor;
use App\Services\RunExtraction\WAHAExtractionDump;
//
use App\Services\Word\SOCWellExtractor;
use App\Services\RunExtraction\SOCExtractionDump;
//
use App\Services\Pdf\AGOCOWellExtractor;
use App\Services\RunExtraction\AGOCOExtractionDump;
//
use App\Services\Pdf\AOOWellExtractor;
use App\Services\RunExtraction\AOOExtractionDump;

// Workover - WAHA
// use App\Services\Excel\WAHAWellExtractorWorkover;
// use App\Services\Pdf\WAHAWellExtractorWorkoverPdf;
// use App\Services\RunExtraction\Workover\WAHAExtractionDumpWorkover;

// SOC
use App\Services\Word\SOCWellExtractorWorkover;
use App\Services\RunExtraction\Workover\SOCExtractionDumpWorkover;


use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser;



class MainExtractorController extends Controller
{
    public function runDrilling()
    {
        $WAHA_fileNames = [
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 11-3-2026.xlsx',
                'date' => '03/11/2026',
            ],
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT 12-3-2026.xlsx',
                'date' => '03/12/2026',
            ],
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 13-3-2026.xlsx',
                'date' => '03/13/2026',
            ],
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 15-3-2026.xlsx',
                'date' => '03/15/2026',
            ],
        ];

        $SOC_fileNames = [
            [
                'name' => 'app/new/SOC DAILY DRLG & WO REPORT_MAR-15-2026.docx',
                'date' => '03/15/2026',
            ],

        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/new/10-03-2026.pdf',
                'date' => '03/10/2026',
            ],
            [
                'name' => 'app/new/11-03-2026.pdf',
                'date' => '03/11/2026',
            ],
            [
                'name' => 'app/new/12-03-2026.pdf',
                'date' => '03/12/2026',
            ],
            [
                'name' => 'app/new/13-03-2026.pdf',
                'date' => '03/13/2026',
            ],
            [
                'name' => 'app/new/14-03-2026.pdf',
                'date' => '03/14/2026',
            ],
            [
                'name' => 'app/new/15-03-2026.pdf',
                'date' => '03/15/2026',
            ],

        ];

        $AOO_fileNames = [
            // [
            //     'name' => 'app/NOC DDR Feb.19.2026.pdf',
            //     'date' => '02/19/2026',
            // ],

        ];



        // return $this->logData($WAHA_fileNames, WAHAWellExtractor::class);
        // $run = new WAHAExtractionDump();
        // return $run->runExtraction($WAHA_fileNames);


        // return $this->logData($SOC_fileNames, SOCWellExtractor::class);
        $run = new SOCExtractionDump();
        return $run->runExtraction($SOC_fileNames);


        // return $this->logData($AGOCO_fileNames, AGOCOWellExtractor::class);
        // $run = new AGOCOExtractionDump();
        // return $run->runExtraction($AGOCO_fileNames);


        return $this->logData($AOO_fileNames, AOOWellExtractor::class);
        $run = new AOOExtractionDump();
        return $run->runExtraction($AOO_fileNames);

    }



    public function runWorkover()
    {
        $SOC_fileNames = [
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-01-2026.docx',
                'date' => '01/01/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-02-2026.docx',
                'date' => '01/02/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-03-2026.docx',
                'date' => '01/03/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-04-2026.docx',
                'date' => '01/04/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-05-2026.docx',
                'date' => '01/05/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-06-2026.docx',
                'date' => '01/06/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-07-2026.docx',
                'date' => '01/07/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-08-2026.docx',
                'date' => '01/08/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-09-2026.docx',
                'date' => '01/09/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-10-2026.docx',
                'date' => '01/10/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-11-2026.docx',
                'date' => '01/11/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-12-2026.docx',
                'date' => '01/12/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-13-2026.docx',
                'date' => '01/13/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-14-2026.docx',
                'date' => '01/14/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-15-2026.docx',
                'date' => '01/15/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-16-2026.docx',
                'date' => '01/16/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-17-2026.docx',
                'date' => '01/17/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-18-2026.docx',
                'date' => '01/18/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-19-2026.docx',
                'date' => '01/19/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-20-2026.docx',
                'date' => '01/20/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-21-2026.docx',
                'date' => '01/21/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-22-2026.docx',
                'date' => '01/22/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-23-2026.docx',
                'date' => '01/23/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-24-2026.docx',
                'date' => '01/24/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-25-2026.docx',
                'date' => '01/25/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-26-2026.docx',
                'date' => '01/26/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-27-2026.docx',
                'date' => '01/27/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-28-2026.docx',
                'date' => '01/28/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-29-2026.docx',
                'date' => '01/29/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-30-2026.docx',
                'date' => '01/30/2026',
            ],
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-31-2026.docx',
                'date' => '01/31/2026',
            ],


        ];



        return $this->logData($SOC_fileNames, SOCWellExtractorWorkover::class);
        $run = new SOCExtractionDumpWorkover();
        return $run->runExtraction($SOC_fileNames);
    }






















    
    private function logData($fileNames, $extractor)
    {
        $allData = [];
        foreach ($fileNames as $value) {
            $filePath = storage_path($value['name']);
            $extractor = new $extractor();
            $data = $extractor->extract($filePath);

            array_push($allData, [count($data) => $data]);
            
            \Log::debug('Data', [$value['name'] => count($data)]);
        }

        return $allData;   
    }
}

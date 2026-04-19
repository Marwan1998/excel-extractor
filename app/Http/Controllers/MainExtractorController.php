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
use App\Services\RunExtraction\Workover\WAHAExtractionDumpWorkover;
use App\Services\Pdf\WAHAWellExtractorWorkover;


// SOC
use App\Services\Word\SOCWellExtractorWorkover;
use App\Services\RunExtraction\Workover\SOCExtractionDumpWorkover;


// AGOCO
use App\Services\Pdf\AGOCOWellExtractorWorkover;
use App\Services\RunExtraction\Workover\AGOCOExtractionDumpWorkover;


use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser;



class MainExtractorController extends Controller
{
    public function runDrilling()
    {
        $WAHA_fileNames = [
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT 2-4-2026.xlsx',
                'date' => '04/02/2026',
            ],
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT 2-4-2026.xlsx',
                'date' => '04/02/2026',
            ],
            [
                'name' => 'app/new/WAHA_DAILY DRILLING SUMMARY REPORT 2-4-2026.xlsx',
                'date' => '04/02/2026',
            ],

        ];

        $SOC_fileNames = [
            [
                'name' => 'app/new/SOC DAILY DRLG & WO REPORT_APR-18-2026.docx',
                'date' => '04/18/2026',
            ],
            [
                'name' => 'app/new/SOC DAILY DRLG & WO REPORT_APR-19-2026.docx',
                'date' => '04/19/2026',
            ],


        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/new/17-04-2026.pdf',
                'date' => '04/17/2026',
            ],
            [
                'name' => 'app/new/18-04-2026.pdf',
                'date' => '04/18/2026',
            ],
            [
                'name' => 'app/new/19-04-2026.pdf',
                'date' => '04/19/2026',
            ],


        ];

        $AOO_fileNames = [
            [
                'name' => 'app/new/NOC DDR-April-08-2026.pdf',
                'date' => '04/08/2026',
            ],
            [
                'name' => 'app/new/NOC DDR-April-09-2026.pdf',
                'date' => '04/09/2026',
            ],
            [
                'name' => 'app/new/NOC DDR-April-10-2026.pdf',
                'date' => '04/10/2026',
            ],
            [
                'name' => 'app/new/NOC DDR-April-11-2026.pdf',
                'date' => '04/11/2026',
            ],
            [
                'name' => 'app/new/NOC DDR-April-12-2026.pdf',
                'date' => '04/12/2026',
            ],

        ];


        foreach ($AOO_fileNames as $value) {
            $filePath = storage_path($value['name']);
            logd(['real' => $value['name'], 'Date' => extractDateFromFileName($filePath)]);
        }
        return 0;


        // return logData($WAHA_fileNames, WAHAWellExtractor::class);
        // $run = new WAHAExtractionDump();
        // return $run->runExtraction($WAHA_fileNames);


        // return logData($SOC_fileNames, SOCWellExtractor::class);
        // $run = new SOCExtractionDump();
        // return $run->runExtraction($SOC_fileNames);

        // return logData($AGOCO_fileNames, AGOCOWellExtractor::class);
        // $run = new AGOCOExtractionDump();
        // return $run->runExtraction($AGOCO_fileNames);


        // NOT fully ready, for now, the summary in not being extracted

        return logData($AOO_fileNames, AOOWellExtractor::class);
        $run = new AOOExtractionDump();
        return $run->runExtraction($AOO_fileNames);


    }


    public function runWorkover()
    {
        ini_set('memory_limit', '20000M');
        // return phpinfo();
        // return 'Memory limit: ' . ini_get('memory_limit');

        $SOC_fileNames = [
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-15-2026.docx',
                'date' => '04/15/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-16-2026.docx',
                'date' => '04/16/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-18-2026.docx',
                'date' => '04/18/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-19-2026.docx',
                'date' => '04/19/2026',
            ],


        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/workover/WORKOVER REPORTS 15-04-2026 .pdf',
                'date' => '04/15/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 16-04-2026 .pdf',
                'date' => '04/16/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 17-04-2026 .pdf',
                'date' => '04/17/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 18-04-2026 .pdf',
                'date' => '04/18/2026',
            ],



        ];

        $WAHA_fileNames = [
            [
                'name' => 'app/workover/WAHA_DAILY WORKOVER SUMMARY REPORT 1-4-2026.pdf',
                'date' => '04/01/2026',
            ],


        ];




        // return logData($WAHA_fileNames, WAHAWellExtractorWorkover::class);
        // $run = new WAHAExtractionDumpWorkover();
        // return $run->runExtraction($WAHA_fileNames);


        // return logData($AGOCO_fileNames, AGOCOWellExtractorWorkover::class);
        // $run = new AGOCOExtractionDumpWorkover();
        // return $run->runExtraction($AGOCO_fileNames);


        // return logData($SOC_fileNames, SOCWellExtractorWorkover::class);
        // $run = new SOCExtractionDumpWorkover();
        // return $run->runExtraction($SOC_fileNames);


        // $extractor = new WAHAWellExtractorWorkover();
        // $data = $extractor->extract(storage_path('app/workover/WAHA_DAILY WORKOVER SUMMARY REPORT (NOC) 1-1-2026.pdf'));
        // return($data);


        return 'Empty';
    }

}

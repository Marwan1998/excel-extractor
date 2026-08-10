<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser;


// ----- Drilling -----
// WAHA
use App\Services\Excel\WAHAWellExtractor;
use App\Services\RunExtraction\WAHAExtractionDump;
//
use App\Services\Pdf\WAHAWellExtractorPDF;
use App\Services\RunExtraction\WAHAExtractionDumpPDF;
// SOC
use App\Services\Word\SOCWellExtractor;
use App\Services\RunExtraction\SOCExtractionDump;
// AGOCO
use App\Services\Pdf\AGOCOWellExtractor;
use App\Services\RunExtraction\AGOCOExtractionDump;
// AOO
use App\Services\Pdf\AOOWellExtractor;
use App\Services\RunExtraction\AOOExtractionDump;

// ----- Workover -----
//  - WAHA
use App\Services\RunExtraction\Workover\WAHAExtractionDumpWorkover;
use App\Services\Pdf\WAHAWellExtractorWorkover;

// SOC
use App\Services\Word\SOCWellExtractorWorkover;
use App\Services\RunExtraction\Workover\SOCExtractionDumpWorkover;

// AGOCO
use App\Services\Pdf\AGOCOWellExtractorWorkover;
use App\Services\RunExtraction\Workover\AGOCOExtractionDumpWorkover;

// AOO
use App\Services\Pdf\AOOWellExtractorWorkover;
use App\Services\RunExtraction\Workover\AOOExtractionDumpWorkover;



class MainExtractorController extends Controller
{
    public function runDrilling()
    {
        $WAHA_fileNames = [
            [
                'name' => 'app/other/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 2-8-2026.pdf',
                'date' => '08/02/2026',
            ],
            [
                'name' => 'app/other/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 3-Aug-2026.pdf',
                'date' => '08/03/2026',
            ],
            [
                'name' => 'app/other/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 4-Aug-2026.pdf',
                'date' => '08/04/2026',
            ],
            [
                'name' => 'app/other/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 5-8-2026.pdf',
                'date' => '08/05/2026',
            ],
            [
                'name' => 'app/other/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 6-Aug-2026.pdf',
                'date' => '08/06/2026',
            ],

        ];

        $SOC_fileNames = [
            [
                'name' => 'app/done/SOC DAILY DRLG & WO REPORT_MAR-31-2026...docx',
                'date' => '07/13/2026',
            ],

        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/done/28-03-2026.pdf',
                'date' => '07/09/2026',
            ],
        ];

        $AOO_fileNames = [
            // [
            //     'name' => 'app/new/NOC_DDR_July_10_2026.pdf',
            //     'date' => '04/12/2026',
            // ],
            // [
            //     'name' => 'app/new/NOC_DDR_July_11_2026.pdf',
            //     'date' => '04/12/2026',
            // ],
            // [
            //     'name' => 'app/new/NOC_DDR_July_12_2026.pdf',
            //     'date' => '04/12/2026',
            // ],

            [
                'name' => 'app/new/NOC_DDR_Agu_02_2026.pdf',
                'date' => '08/02/2026',
            ],
            [
                'name' => 'app/new/NOC_DDR_Aug_03_2026.pdf',
                'date' => '08/03/2026',
            ],
            [
                'name' => 'app/new/NOC_DDR_Aug_03_2026.pdf',
                'date' => '08/03/2026',
            ],
            [
                'name' => 'app/new/NOC_DDR_Aug_04_2026.pdf',
                'date' => '08/04/2026',
            ],
            [
                'name' => 'app/new/NOC_DDR_Aug_05_2026.pdf',
                'date' => '08/05/2026',
            ],
            [
                'name' => 'app/new/NOC_DDR_Aug_06_2026.pdf',
                'date' => '08/06/2026',
            ],

        ];


        // return logData($WAHA_fileNames, WAHAWellExtractor::class);
        // $run = new WAHAExtractionDump();
        // return $run->runExtraction($WAHA_fileNames);


        return logData($WAHA_fileNames, WAHAWellExtractorPDF::class);
        $run = new WAHAExtractionDumpPDF();
        return $run->runExtraction($WAHA_fileNames);



        // return logData($SOC_fileNames, SOCWellExtractor::class);
        // $run = new SOCExtractionDump();
        // return $run->runExtraction($SOC_fileNames);

        // return logData($AGOCO_fileNames, AGOCOWellExtractor::class);
        // $run = new AGOCOExtractionDump();
        // return $run->runExtraction($AGOCO_fileNames);


        // return logData($AOO_fileNames, AOOWellExtractor::class);
        // $run = new AOOExtractionDump();
        // return $run->runExtraction($AOO_fileNames);


    }


    public function runWorkover()
    {
        ini_set('memory_limit', '20000M');
        // return phpinfo();
        // return 'Memory limit: ' . ini_get('memory_limit');

        $SOC_fileNames = [
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-07-2026.docx',
                'date' => '07/07/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-08-2026.docx',
                'date' => '07/08/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-09-2026.docx',
                'date' => '07/09/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-10-2026.docx',
                'date' => '07/10/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-11-2026.docx',
                'date' => '07/11/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-12-2026.docx',
                'date' => '07/12/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_JULY-13-2026.docx',
                'date' => '07/13/2026',
            ],


        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/workover/WORKOVER REPORTS 07-07-2026 .pdf',
                'date' => '07/07/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 11-07-2026 .pdf',
                'date' => '07/11/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 13-07-2026 .pdf',
                'date' => '07/13/2026',
            ],



        ];

        $WAHA_fileNames = [
            [
                'name' => 'app/workover/WAHA_DAILY WORKOVER SUMMARY REPORT 1-4-2026.pdf',
                'date' => '04/01/2026',
            ],


        ];

        $AOO_fileNames = [
            // [
            //     'name' => 'app/workover/NOC_WOV_July_10_2026.pdf',
            //     'date' => '07/10/2026',
            // ],
            // [
            //     'name' => 'app/workover/NOC_WOV_July_11_2026.pdf',
            //     'date' => '07/11/2026',
            // ],
            // [
            //     'name' => 'app/workover/NOC_WOV_July_12_2026.pdf',
            //     'date' => '07/12/2026',
            // ],
            [
                'name' => 'app/workover/NOC_DWR_Agu_02_2026.pdf',
                'date' => '08/02/2026',
            ],
            [
                'name' => 'app/workover/NOC_WOV_Aug_03_2026.pdf',
                'date' => '08/03/2026',
            ],
            [
                'name' => 'app/workover/NOC_WOV_Aug_04_2026.pdf',
                'date' => '08/04/2026',
            ],
            [
                'name' => 'app/workover/NOC_WOV_Aug_05_2026.pdf',
                'date' => '08/05/2026',
            ],
            [
                'name' => 'app/workover/NOC_WOV_Aug_06_2026.pdf',
                'date' => '08/06/2026',
            ],


        ];        




        return logData($WAHA_fileNames, WAHAWellExtractorWorkover::class);
        $run = new WAHAExtractionDumpWorkover();
        return $run->runExtraction($WAHA_fileNames);

        // return logData($AGOCO_fileNames, AGOCOWellExtractorWorkover::class);
        // $run = new AGOCOExtractionDumpWorkover();
        // return $run->runExtraction($AGOCO_fileNames);

        // return logData($SOC_fileNames, SOCWellExtractorWorkover::class);
        // $run = new SOCExtractionDumpWorkover();
        // return $run->runExtraction($SOC_fileNames);

        // return logData($AOO_fileNames, AOOWellExtractorWorkover::class);
        // $run = new AOOExtractionDumpWorkover();
        // return $run->runExtraction($AOO_fileNames);



        return 'Empty';
    }

}

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
                'name' => 'app/new/SOC DAILY DRLG & WO REPORT_APR-12-2026.docx',
                'date' => '04/12/2026',
            ],


        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/new/12-04-2026.pdf',
                'date' => '04/12/2026',
            ],
    

        ];





        // return $this->logData($WAHA_fileNames, WAHAWellExtractor::class);
        // $run = new WAHAExtractionDump();
        // return $run->runExtraction($WAHA_fileNames);


        // // return $this->logData($SOC_fileNames, SOCWellExtractor::class);
        // $run = new SOCExtractionDump();
        // return $run->runExtraction($SOC_fileNames);

        // return $this->logData($AGOCO_fileNames, AGOCOWellExtractor::class);
        // $run = new AGOCOExtractionDump();
        // return $run->runExtraction($AGOCO_fileNames);














        $AOO_fileNames = [
            // [
            //     'name' => 'app/NOC DDR Feb.19.2026.pdf',
            //     'date' => '02/19/2026',
            // ],

        ];

        return $this->logData($AOO_fileNames, AOOWellExtractor::class);
        $run = new AOOExtractionDump();
        return $run->runExtraction($AOO_fileNames);

    }



    public function runWorkover()
    {
        ini_set('memory_limit', '10000M');
        // return phpinfo();
        // return 'Memory limit: ' . ini_get('memory_limit');

        $SOC_fileNames = [

            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-01-2026.docx',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-02-2026.docx',
            //     'date' => '01/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-03-2026.docx',
            //     'date' => '01/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-04-2026.docx',
            //     'date' => '01/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-05-2026.docx',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-06-2026.docx',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-07-2026.docx',
            //     'date' => '01/07/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-08-2026.docx',
            //     'date' => '01/08/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-09-2026.docx',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-10-2026.docx',
            //     'date' => '01/10/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-11-2026.docx',
            //     'date' => '01/11/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-12-2026.docx',
            //     'date' => '01/12/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-13-2026.docx',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-14-2026.docx',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-15-2026.docx',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-16-2026.docx',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-17-2026.docx',
            //     'date' => '01/17/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-18-2026.docx',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-19-2026.docx',
            //     'date' => '01/19/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-20-2026.docx',
            //     'date' => '01/20/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-21-2026.docx',
            //     'date' => '01/21/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-22-2026.docx',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-23-2026.docx',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-24-2026.docx',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-25-2026.docx',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-26-2026.docx',
            //     'date' => '01/26/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-27-2026.docx',
            //     'date' => '01/27/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-28-2026.docx',
            //     'date' => '01/28/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-29-2026.docx',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-30-2026.docx',
            //     'date' => '01/30/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_JAN-31-2026.docx',
            //     'date' => '01/31/2026',
            // ],

            

            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-01-2026.docx',
            //     'date' => '02/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-02-2026.docx',
            //     'date' => '02/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-03-2026.docx',
            //     'date' => '02/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-04-2026.docx',
            //     'date' => '02/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-05-2026.docx',
            //     'date' => '02/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-06-2026.docx',
            //     'date' => '02/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-07-2026.docx',
            //     'date' => '02/07/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-08-2026.docx',
            //     'date' => '02/08/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-09-2026.docx',
            //     'date' => '02/09/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-10-2026.docx',
            //     'date' => '02/10/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-11-2026.docx',
            //     'date' => '02/11/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-12-2026.docx',
            //     'date' => '02/12/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-13-2026.docx',
            //     'date' => '02/13/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-14-2026.docx',
            //     'date' => '02/14/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-15-2026.docx',
            //     'date' => '02/15/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-16-2026.docx',
            //     'date' => '02/16/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-17-2026.docx',
            //     'date' => '02/17/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-18-2026.docx',
            //     'date' => '02/18/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-19-2026.docx',
            //     'date' => '02/19/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-20-2026.docx',
            //     'date' => '02/20/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-21-2026.docx',
            //     'date' => '02/21/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-22-2026.docx',
            //     'date' => '02/22/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-23-2026.docx',
            //     'date' => '02/23/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-24-2026.docx',
            //     'date' => '02/24/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-25-2026.docx',
            //     'date' => '02/25/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-26-2026.docx',
            //     'date' => '02/26/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_FEB-27-2026.docx',
            //     'date' => '02/27/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG  WO REPORT_FEB-28-2026.docx',
            //     'date' => '02/28/2026',
            // ],

            //

            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-01-2026.docx',
            //     'date' => '03/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-02-2026.docx',
            //     'date' => '03/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-03-2026.docx',
            //     'date' => '03/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-04-2026.docx',
            //     'date' => '03/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-05-2026.docx',
            //     'date' => '03/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-06-2026.docx',
            //     'date' => '03/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-07-2026.docx',
            //     'date' => '03/07/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-08-2026.docx',
            //     'date' => '03/08/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-09-2026.docx',
            //     'date' => '03/09/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-10-2026.docx',
            //     'date' => '03/10/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-11-2026.docx',
            //     'date' => '03/11/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-12-2026.docx',
            //     'date' => '03/12/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-13-2026.docx',
            //     'date' => '03/13/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-14-2026.docx',
            //     'date' => '03/14/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-15-2026.docx',
            //     'date' => '03/15/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-16-2026.docx',
            //     'date' => '03/16/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-17-2026.docx',
            //     'date' => '03/17/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-18-2026.docx',
            //     'date' => '03/18/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-19-2026.docx',
            //     'date' => '03/19/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-20-2026.docx',
            //     'date' => '03/20/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-21-2026.docx',
            //     'date' => '03/21/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-22-2026.docx',
            //     'date' => '03/22/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-23-2026.docx',
            //     'date' => '03/23/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-24-2026.docx',
            //     'date' => '03/24/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-25-2026.docx',
            //     'date' => '03/25/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-26-2026.docx',
            //     'date' => '03/26/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-27-2026.docx',
            //     'date' => '03/27/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-28-2026.docx',
            //     'date' => '03/28/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-29-2026.docx',
            //     'date' => '03/29/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-30-2026.docx',
            //     'date' => '03/30/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_MAR-31-2026.docx',
            //     'date' => '03/31/2026',
            // ],

            //

            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-01-2026.docx',
            //     'date' => '04/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-02-2026.docx',
            //     'date' => '04/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-03-2026...docx',
            //     'date' => '04/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-04-2026...docx',
            //     'date' => '04/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-05-2026.docx',
            //     'date' => '04/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-06-2026.docx',
            //     'date' => '04/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-07-2026.docx',
            //     'date' => '04/07/2026',
            // ],
            // [
            //     'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-08-2026.docx',
            //     'date' => '04/08/2026',
            // ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-09-2026.docx',
                'date' => '04/09/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-10-2026.docx',
                'date' => '04/10/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-11-2026.docx',
                'date' => '04/11/2026',
            ],
            [
                'name' => 'app/workover/SOC DAILY DRLG & WO REPORT_APR-12-2026.docx',
                'date' => '04/12/2026',
            ],



        ];

        $AGOCO_fileNames = [

            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 01-01-2026 .pdf',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 02-01-2026 .pdf',
            //     'date' => '01/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 03-01-2026 .pdf',
            //     'date' => '01/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 04-01-2026 .pdf',
            //     'date' => '01/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 05-01-2026 .pdf',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 06-01-2026 .pdf',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 07-01-2026 .pdf',
            //     'date' => '01/07/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 08-01-2026 .pdf',
            //     'date' => '01/08/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 09-01-2026 .pdf',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 10-01-2026 .pdf',
            //     'date' => '01/10/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 11-01-2026 .pdf',
            //     'date' => '01/11/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 12-01-2026 .pdf',
            //     'date' => '01/12/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 13-01-2026 .pdf',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 14-01-2026 .pdf',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 15-01-2026 .pdf',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 16-01-2026 .pdf',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 17-01-2026 .pdf',
            //     'date' => '01/17/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 18-01-2026 .pdf',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 19-01-2026 .pdf',
            //     'date' => '01/19/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 22-01-2026 .pdf',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 23-01-2026 .pdf',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 24-01-2026 .pdf',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 25-01-2026 .pdf',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 26-01-2026 .pdf',
            //     'date' => '01/26/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 27-01-2026 .pdf',
            //     'date' => '01/27/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 28-01-2026 .pdf',
            //     'date' => '01/28/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 29-01-2026 .pdf',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 30-01-2026 .pdf',
            //     'date' => '01/30/2026',
            // ],

            //

            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 01-02-2026 .pdf',
            //     'date' => '02/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 02-02-2026 .pdf',
            //     'date' => '02/02/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 03-02-2026 .pdf',
            //     'date' => '02/03/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 04-02-2026 .pdf',
            //     'date' => '02/04/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 05-02-2026 .pdf',
            //     'date' => '02/05/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 06-02-2026 .pdf',
            //     'date' => '02/06/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 07-02-2026 .pdf',
            //     'date' => '02/07/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 08-02-2026 .pdf',
            //     'date' => '02/08/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 09-02-2026 .pdf',
            //     'date' => '02/09/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 10-02-2026 .pdf',
            //     'date' => '02/10/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 11-02-2026 .pdf',
            //     'date' => '02/11/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 12-02-2026 .pdf',
            //     'date' => '02/12/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 13-02-2026 .pdf',
            //     'date' => '02/13/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 14-02-2026 .pdf',
            //     'date' => '02/14/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 15-02-2026 .pdf',
            //     'date' => '02/15/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 16-02-2026 .pdf',
            //     'date' => '02/16/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 17-02-2026 .pdf',
            //     'date' => '02/17/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 18-02-2026 .pdf',
            //     'date' => '02/18/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 19-02-2026 .pdf',
            //     'date' => '02/19/2026',
            // ],                    
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 20-02-2026 .pdf',
            //     'date' => '02/20/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 21-02-2026 .pdf',
            //     'date' => '02/21/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 22-02-2026 .pdf',
            //     'date' => '02/22/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 23-02-2026 .pdf',
            //     'date' => '02/23/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 24-02-2026 .pdf',
            //     'date' => '02/24/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 25-02-2026 .pdf',
            //     'date' => '02/25/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 26-02-2026 .pdf',
            //     'date' => '02/26/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 27-02-2026 .pdf',
            //     'date' => '02/27/2026',
            // ],           
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 28-02-2026 .pdf',
            //     'date' => '02/28/2026',
            // ],
            
            //

            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 01-03-2026 .pdf',
            //     'date' => '03/01/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 02-03-2026 .pdf',
            //     'date' => '03/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 03-03-2026 .pdf',
            //     'date' => '03/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 04-03-2026 .pdf',
            //     'date' => '03/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 05-03-2026 .pdf',
            //     'date' => '03/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 06-03-2026 .pdf',
            //     'date' => '03/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 07-03-2026 .pdf',
            //     'date' => '03/07/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 08-03-2026 .pdf',
            //     'date' => '03/08/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 09-03-2026 .pdf',
            //     'date' => '03/09/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 10-03-2026 .pdf',
            //     'date' => '03/10/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 11-03-2026 .pdf',
            //     'date' => '03/11/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 12-03-2026 .pdf',
            //     'date' => '03/12/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 13-03-2026 .pdf',
            //     'date' => '03/13/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 14-03-2026 .pdf',
            //     'date' => '03/14/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 15-03-2026 .pdf',
            //     'date' => '03/15/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 17-03-2026 .pdf',
            //     'date' => '03/17/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 18-03-2026 .pdf',
            //     'date' => '03/18/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 19-03-2026 .pdf',
            //     'date' => '03/19/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 20-03-2026 .pdf',
            //     'date' => '03/20/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 21-03-2026 .pdf',
            //     'date' => '03/21/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 22-03-2026 .pdf',
            //     'date' => '03/22/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 23-03-2026 .pdf',
            //     'date' => '03/23/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 24-03-2026 .pdf',
            //     'date' => '03/24/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 25-03-2026 .pdf',
            //     'date' => '03/25/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 26-03-2026 .pdf',
            //     'date' => '03/26/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 27-03-2026 .pdf',
            //     'date' => '03/27/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 28-03-2026 .pdf',
            //     'date' => '03/28/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 29-03-2026 .pdf',
            //     'date' => '03/29/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 30-03-2026 .pdf',
            //     'date' => '03/30/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 31-03-2026 .pdf',
            //     'date' => '03/31/2026',
            // ],
          
            //

            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 01-04-2026 .pdf',
            //     'date' => '04/02/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 02-04-2026 .pdf',
            //     'date' => '04/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 03-04-2026 .pdf',
            //     'date' => '04/03/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 04-04-2026 .pdf',
            //     'date' => '04/04/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 05-04-2026 .pdf',
            //     'date' => '04/05/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 06-04-2026 .pdf',
            //     'date' => '04/06/2026',
            // ],
            // [
            //     'name' => 'app/workover/WORKOVER REPORTS 07-04-2026 .pdf',
            //     'date' => '04/07/2026',
            // ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 08-04-2026 .pdf',
                'date' => '04/08/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 09-04-2026 .pdf',
                'date' => '04/09/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 10-04-2026 .pdf',
                'date' => '04/10/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 11-04-2026 .pdf',
                'date' => '04/11/2026',
            ],
            [
                'name' => 'app/workover/WORKOVER REPORTS 12-04-2026 .pdf',
                'date' => '04/12/2026',
            ],



        ];

        $WAHA_fileNames = [
            [
                'name' => 'app/workover/WAHA_DAILY WORKOVER SUMMARY REPORT (NOC) 1-1-2026.pdf',
                'date' => '01/01/2026',
            ],
            [
                'name' => 'app/workover/WAHA_DAILY WORKOVER SUMMARY REPORT (NOC) 2-1-2026.pdf',
                'date' => '01/02/2026',
            ],


        ];






        // return $this->logData($WAHA_fileNames, WAHAWellExtractorWorkover::class);
        // $run = new WAHAExtractionDumpWorkover();
        // return $run->runExtraction($WAHA_fileNames);


        // return $this->logData($AGOCO_fileNames, AGOCOWellExtractorWorkover::class);
        // $run = new AGOCOExtractionDumpWorkover();
        // return $run->runExtraction($AGOCO_fileNames);


        // return $this->logData($SOC_fileNames, SOCWellExtractorWorkover::class);
        // $run = new SOCExtractionDumpWorkover();
        // return $run->runExtraction($SOC_fileNames);


        // $extractor = new WAHAWellExtractorWorkover();
        // $data = $extractor->extract(storage_path('app/workover/WAHA_DAILY WORKOVER SUMMARY REPORT (NOC) 1-1-2026.pdf'));
        // return($data);
    }











/*

            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-01-2026.docx',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-02-2026.docx',
            //     'date' => '01/02/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-03-2026.docx',
            //     'date' => '01/03/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-04-2026.docx',
            //     'date' => '01/04/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-05-2026.docx',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-06-2026.docx',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-07-2026.docx',
            //     'date' => '01/07/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-08-2026.docx',
            //     'date' => '01/08/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-09-2026.docx',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-10-2026.docx',
            //     'date' => '01/10/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-11-2026.docx',
            //     'date' => '01/11/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-12-2026.docx',
            //     'date' => '01/12/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-13-2026.docx',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-14-2026.docx',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-15-2026.docx',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-16-2026.docx',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-17-2026.docx',
            //     'date' => '01/17/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-18-2026.docx',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-19-2026.docx',
            //     'date' => '01/19/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-20-2026.docx',
            //     'date' => '01/20/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-21-2026.docx',
            //     'date' => '01/21/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-22-2026.docx',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-23-2026.docx',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-24-2026.docx',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-25-2026.docx',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-26-2026.docx',
            //     'date' => '01/26/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-27-2026.docx',
            //     'date' => '01/27/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-28-2026.docx',
            //     'date' => '01/28/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-29-2026.docx',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-30-2026.docx',
            //     'date' => '01/30/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_JAN-31-2026.docx',
            //     'date' => '01/31/2026',
            // ],

            //

            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-01-2026.docx',
            //     'date' => '02/01/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-02-2026.docx',
            //     'date' => '02/02/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-03-2026.docx',
            //     'date' => '02/03/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-04-2026.docx',
            //     'date' => '02/04/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-05-2026.docx',
            //     'date' => '02/05/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-06-2026.docx',
            //     'date' => '02/06/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-07-2026.docx',
            //     'date' => '02/07/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-08-2026.docx',
            //     'date' => '02/08/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-09-2026.docx',
            //     'date' => '02/09/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-10-2026.docx',
            //     'date' => '02/10/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-11-2026.docx',
            //     'date' => '02/11/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-12-2026.docx',
            //     'date' => '02/12/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-13-2026.docx',
            //     'date' => '02/13/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-14-2026.docx',
            //     'date' => '02/14/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-15-2026.docx',
            //     'date' => '02/15/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-16-2026.docx',
            //     'date' => '02/16/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-17-2026.docx',
            //     'date' => '02/17/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-18-2026.docx',
            //     'date' => '02/18/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-19-2026.docx',
            //     'date' => '02/19/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-20-2026.docx',
            //     'date' => '02/20/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-21-2026.docx',
            //     'date' => '02/21/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-22-2026.docx',
            //     'date' => '02/22/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-23-2026.docx',
            //     'date' => '02/23/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-24-2026.docx',
            //     'date' => '02/24/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-25-2026.docx',
            //     'date' => '02/25/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-26-2026.docx',
            //     'date' => '02/26/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-27-2026.docx',
            //     'date' => '02/27/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG  WO REPORT_FEB-28-2026.docx',
            //     'date' => '02/28/2026',
            // ],

            //

            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-01-2026.docx',
            //     'date' => '03/01/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-02-2026.docx',
            //     'date' => '03/02/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-03-2026.docx',
            //     'date' => '03/03/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-04-2026.docx',
            //     'date' => '03/04/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-05-2026.docx',
            //     'date' => '03/05/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-06-2026.docx',
            //     'date' => '03/06/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-07-2026.docx',
            //     'date' => '03/07/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-08-2026.docx',
            //     'date' => '03/08/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-09-2026.docx',
            //     'date' => '03/09/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-10-2026.docx',
            //     'date' => '03/10/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-11-2026.docx',
            //     'date' => '03/11/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-12-2026.docx',
            //     'date' => '03/12/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-13-2026.docx',
            //     'date' => '03/13/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-14-2026.docx',
            //     'date' => '03/14/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-15-2026.docx',
            //     'date' => '03/15/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-16-2026.docx',
            //     'date' => '03/16/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-17-2026.docx',
            //     'date' => '03/17/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-18-2026.docx',
            //     'date' => '03/18/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-19-2026.docx',
            //     'date' => '03/19/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-20-2026.docx',
            //     'date' => '03/20/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-21-2026.docx',
            //     'date' => '03/21/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-22-2026.docx',
            //     'date' => '03/22/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-23-2026.docx',
            //     'date' => '03/23/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-24-2026.docx',
            //     'date' => '03/24/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-25-2026.docx',
            //     'date' => '03/25/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-26-2026.docx',
            //     'date' => '03/26/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-27-2026.docx',
            //     'date' => '03/27/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-28-2026.docx',
            //     'date' => '03/28/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-29-2026.docx',
            //     'date' => '03/29/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-30-2026.docx',
            //     'date' => '03/30/2026',
            // ],
            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_MAR-31-2026.docx',
            //     'date' => '03/31/2026',
            // ],

            //


*/











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

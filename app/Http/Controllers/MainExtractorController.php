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


class MainExtractorController extends Controller
{
    public function run()
    {
        $WAHA_fileNames = [
            // [
            //     'name' => 'app/waha-report-1-1-2026.xlsx',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-2-1-2026.xlsx',
            //     'date' => '01/02/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-3-1-2026.xlsx',
            //     'date' => '1/03/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-5-1-2026.xlsx',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-6-1-2026.xlsx',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-8-1-2026.xlsx',
            //     'date' => '01/08/2026',
            // ],

            // [
            //     'name' => 'app/waha-report-9-1-2026.xlsx',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-10-1-2026.xlsx',
            //     'date' => '01/10/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-13-1-2026.xlsx',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-14-1-2026.xlsx',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-15-1-2026.xlsx',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-16-1-2026.xlsx',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-18-1-2026.xlsx',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-19-1-2026.xlsx',
            //     'date' => '01/19/2026',
            // ],

            // //

            // [
            //     'name' => 'app/waha-report-20-1-2026.xlsx',
            //     'date' => '01/20/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-22-1-2026.xlsx',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-23-1-2026.xlsx',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-24-1-2026.xlsx',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-25-1-2026.xlsx',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-27-1-2026.xlsx',
            //     'date' => '01/27/2026',
            // ],            
            // [
            //     'name' => 'app/waha-report-28-1-2026.xlsx',
            //     'date' => '01/28/2026',
            // ],            
            // [
            //     'name' => 'app/waha-report-29-1-2026.xlsx',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-30-1-2026.xlsx',
            //     'date' => '01/30/2026',
            // ],
            // [
            //     'name' => 'app/waha-report-31-1-2026.xlsx',
            //     'date' => '01/31/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_3-2-2026.xlsx',
            //     'date' => '02/03/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_5-2-2026.xlsx',
            //     'date' => '02/05/2026',
            // ],
            
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 7-2-2026.xlsx',
            //     'date' => '02/07/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 8-2-2026.xlsx',
            //     'date' => '02/08/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 9-2-2026.xlsx',
            //     'date' => '02/09/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 10-2-2026.xlsx',
            //     'date' => '02/10/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 11-2-2026.xlsx',
            //     'date' => '02/11/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 12-2-2026.xlsx',
            //     'date' => '02/12/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 13-2-2026.xlsx',
            //     'date' => '02/13/2026',
            // ],
            // [
            //     'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 14-2-2026.xlsx',
            //     'date' => '02/14/2026',
            // ],
            [
                'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 15-2-2026.xlsx',
                'date' => '02/15/2026',
            ],

        ];

        $SOC_fileNames = [
            // [
            //     'name' => 'app/JAN-01-2026.docx',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/JAN-02-2026.docx',
            //     'date' => '01/02/2026',
            // ],
            // [
            //     'name' => 'app/JAN-03-2026.docx',
            //     'date' => '01/03/2026',
            // ],
            // [
            //     'name' => 'app/JAN-04-2026.docx',
            //     'date' => '01/04/2026',
            // ],
            // [
            //     'name' => 'app/JAN-05-2026.docx',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/JAN-06-2026.docx',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/JAN-07-2026.docx',
            //     'date' => '01/07/2026',
            // ],
            // [
            //     'name' => 'app/JAN-08-2026.docx',
            //     'date' => '01/08/2026',
            // ],
            // [
            //     'name' => 'app/JAN-09-2026.docx',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/JAN-10-2026.docx',
            //     'date' => '01/10/2026',
            // ],
            // //
            // [
            //     'name' => 'app/JAN-11-2026.docx',
            //     'date' => '01/11/2026',
            // ],
            // [
            //     'name' => 'app/JAN-12-2026.docx',
            //     'date' => '01/12/2026',
            // ],
            // [
            //     'name' => 'app/JAN-13-2026.docx',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/JAN-14-2026.docx',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/JAN-15-2026.docx',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/JAN-16-2026.docx',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/JAN-17-2026.docx',
            //     'date' => '01/17/2026',
            // ],
            // [
            //     'name' => 'app/JAN-18-2026.docx',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/JAN-19-2026.docx',
            //     'date' => '01/19/2026',
            // ],
            // [
            //     'name' => 'app/JAN-20-2026.docx',
            //     'date' => '01/20/2026',
            // ],
            // //
            // [
            //     'name' => 'app/JAN-21-2026.docx',
            //     'date' => '01/21/2026',
            // ],
            // [
            //     'name' => 'app/JAN-22-2026.docx',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/JAN-23-2026.docx',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/JAN-24-2026.docx',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/JAN-25-2026.docx',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/JAN-26-2026.docx',
            //     'date' => '01/26/2026',
            // ],
            // [
            //     'name' => 'app/JAN-27-2026.docx',
            //     'date' => '01/27/2026',
            // ],
            // [
            //     'name' => 'app/JAN-28-2026.docx',
            //     'date' => '01/28/2026',
            // ],
            // [
            //     'name' => 'app/JAN-29-2026.docx',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/JAN-30-2026.docx',
            //     'date' => '01/30/2026',
            // ],
            // [
            //     'name' => 'app/JAN-31-2026.docx',
            //     'date' => '01/31/2026',
            // ],

            // [
            //     'name' => 'app/SOC_FEB-01-2026.docx',
            //     'date' => '02/01/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-02-2026.docx',
            //     'date' => '02/02/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-03-2026.docx',
            //     'date' => '02/03/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-04-2026.docx',
            //     'date' => '02/04/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-05-2026.docx',
            //     'date' => '02/05/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-06-2026.docx',
            //     'date' => '02/06/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-07-2026.docx',
            //     'date' => '02/07/2026',
            // ],
            // [
            //     'name' => 'app/SOC_FEB-08-2026.docx',
            //     'date' => '02/08/2026',
            // ],

            //

            // [
            //     'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-09-2026.docx',
            //     'date' => '02/09/2026',
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

            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-15-2026.docx',
                'date' => '02/15/2026',
            ],


        ];

        $AGOCO_fileNames = [
            // [
            //     'name' => 'app/01-01-2026.pdf',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/02-01-2026.pdf',
            //     'date' => '01/02/2026',
            // ],
            // [
            //     'name' => 'app/03-01-2026.pdf',
            //     'date' => '01/03/2026',
            // ],
            // [
            //     'name' => 'app/04-01-2026.pdf',
            //     'date' => '01/04/2026',
            // ],
            // [
            //     'name' => 'app/05-01-2026.pdf',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/06-01-2026.pdf',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/07-01-2026.pdf',
            //     'date' => '01/07/2026',
            // ],
            // [
            //     'name' => 'app/08-01-2026.pdf',
            //     'date' => '01/08/2026',
            // ],
            // [
            //     'name' => 'app/09-01-2026.pdf',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/10-01-2026.pdf',
            //     'date' => '01/10/2026',
            // ],
            // //  
            // [
            //     'name' => 'app/11-01-2026.pdf',
            //     'date' => '01/11/2026',
            // ],
            // [
            //     'name' => 'app/12-01-2026.pdf',
            //     'date' => '01/12/2026',
            // ],
            // [
            //     'name' => 'app/13-01-2026.pdf',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/14-01-2026.pdf',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/15-01-2026.pdf',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/16-01-2026.pdf',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/17-01-2026.pdf',
            //     'date' => '01/17/2026',
            // ],
            // [
            //     'name' => 'app/18-01-2026.pdf',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/19-01-2026.pdf',
            //     'date' => '01/19/2026',
            // ],
            // [
            //     'name' => 'app/20-01-2026.pdf',
            //     'date' => '01/20/2026',
            // ],
            // //
            // [
            //     'name' => 'app/21-01-2026.pdf',
            //     'date' => '01/21/2026',
            // ],
            // [
            //     'name' => 'app/22-01-2026.pdf',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/23-01-2026.pdf',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/24-01-2026.pdf',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/25-01-2026.pdf',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/26-01-2026.pdf',
            //     'date' => '01/26/2026',
            // ],
            // [
            //     'name' => 'app/27-01-2026.pdf',
            //     'date' => '01/27/2026',
            // ],
            // [
            //     'name' => 'app/28-01-2026.pdf',
            //     'date' => '01/28/2026',
            // ],
            // [
            //     'name' => 'app/29-01-2026.pdf',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/30-01-2026.pdf',
            //     'date' => '01/30/2026',
            // ],
            // [
            //     'name' => 'app/31-01-2026.pdf',
            //     'date' => '01/31/2026',
            // ],

            //

            // [
            //     'name' => 'app/01-02-2026.pdf',
            //     'date' => '02/01/2026',
            // ],
            // [
            //     'name' => 'app/02-02-2026.pdf',
            //     'date' => '02/02/2026',
            // ],
            // [
            //     'name' => 'app/03-02-2026.pdf',
            //     'date' => '02/03/2026',
            // ],
            // [
            //     'name' => 'app/04-02-2026.pdf',
            //     'date' => '02/04/2026',
            // ],
            // [
            //     'name' => 'app/05-02-2026.pdf',
            //     'date' => '02/05/2026',
            // ],
            // [
            //     'name' => 'app/06-02-2026.pdf',
            //     'date' => '02/06/2026',
            // ],
            // [
            //     'name' => 'app/07-02-2026.pdf',
            //     'date' => '02/07/2026',
            // ],
            // [
            //     'name' => 'app/08-02-2026.pdf',
            //     'date' => '02/08/2026',
            // ],
            // [
            //     'name' => 'app/09-02-2026.pdf',
            //     'date' => '02/09/2026',
            // ],
            // [
            //     'name' => 'app/10-02-2026.pdf',
            //     'date' => '02/10/2026',
            // ],
            // [
            //     'name' => 'app/11-02-2026.pdf',
            //     'date' => '02/11/2026',
            // ],
            // [
            //     'name' => 'app/12-02-2026.pdf',
            //     'date' => '02/12/2026',
            // ],
            // [
            //     'name' => 'app/13-02-2026.pdf',
            //     'date' => '02/13/2026',
            // ],
            // [
            //     'name' => 'app/14-02-2026.pdf',
            //     'date' => '02/14/2026',
            // ],
            [
                'name' => 'app/15-02-2026.pdf',
                'date' => '02/15/2026',
            ],


        ];




        return $this->logData($WAHA_fileNames, WAHAWellExtractor::class);
        $run = new WAHAExtractionDump();
        return $run->runExtraction($WAHA_fileNames);


        // return $this->logData($SOC_fileNames, SOCWellExtractor::class);
        // $run = new SOCExtractionDump();
        // return $run->runExtraction($SOC_fileNames);

        
        // return $this->logData($AGOCO_fileNames, AGOCOWellExtractor::class);
        // $run = new AGOCOExtractionDump();
        // return $run->runExtraction($AGOCO_fileNames);
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

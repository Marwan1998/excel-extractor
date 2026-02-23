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
//



class MainExtractorController extends Controller
{
    public function run()
    {
        $WAHA_fileNames = [
            [
                'name' => 'app/WAHA_DAILY DRILLING SUMMARY REPORT (NOC) 22-2-2026.xlsx',
                'date' => '02/22/2026',
            ],

        ];

        $SOC_fileNames = [
            [
                'name' => 'app/SOC DAILY DRLG & WO REPORT_FEB-22-2026.docx',
                'date' => '02/22/2026',
            ],


        ];

        $AGOCO_fileNames = [
            [
                'name' => 'app/22-02-2026.pdf',
                'date' => '02/22/2026',
            ],

        ];

        $AOO_fileNames = [
            // [
            //     'name' => 'app/NOC DDR Jan.01.2026.pdf',
            //     'date' => '01/01/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.03.2026.pdf',
            //     'date' => '01/03/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.04.2026.pdf',
            //     'date' => '01/04/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.05.2026.pdf',
            //     'date' => '01/05/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.06.2026.pdf',
            //     'date' => '01/06/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.07.2026.pdf',
            //     'date' => '01/07/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.08.2026.pdf',
            //     'date' => '01/08/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.09.2026.pdf',
            //     'date' => '01/09/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.10.2026.pdf',
            //     'date' => '01/10/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.11.2026.pdf',
            //     'date' => '01/11/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.13.2026.pdf',
            //     'date' => '01/13/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.14.2026.pdf',
            //     'date' => '01/14/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.15.2026.pdf',
            //     'date' => '01/15/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.16.2026.pdf',
            //     'date' => '01/16/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.17.2026.pdf',
            //     'date' => '01/17/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.18.2026.pdf',
            //     'date' => '01/18/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.19.2026.pdf',
            //     'date' => '01/19/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.20.2026.pdf',
            //     'date' => '01/20/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.21.2026.pdf',
            //     'date' => '01/21/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.22.2026.pdf',
            //     'date' => '01/22/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.23.2026.pdf',
            //     'date' => '01/23/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.24.2026.pdf',
            //     'date' => '01/24/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.25.2026.pdf',
            //     'date' => '01/25/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.26.2026.pdf',
            //     'date' => '01/26/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.27.2026.pdf',
            //     'date' => '01/27/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.28.2026.pdf',
            //     'date' => '01/28/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.29.2026.pdf',
            //     'date' => '01/29/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.30.2026.pdf',
            //     'date' => '01/30/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Jan.31.2026.pdf',
            //     'date' => '01/31/2026',
            // ],
            //
            // [
            //     'name' => 'app/NOC DDR Feb.01.2026.pdf',
            //     'date' => '02/01/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.02.2026.pdf',
            //     'date' => '02/02/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.03.2026.pdf',
            //     'date' => '02/03/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.04.2026.pdf',
            //     'date' => '02/04/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.05.2026.pdf',
            //     'date' => '02/05/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.06.2026.pdf',
            //     'date' => '02/06/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.07.2026.pdf',
            //     'date' => '02/07/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.08.2026.pdf',
            //     'date' => '02/08/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.09.2026.pdf',
            //     'date' => '02/09/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.10.2026.pdf',
            //     'date' => '02/10/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.11.2026.pdf',
            //     'date' => '02/11/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.12.2026.pdf',
            //     'date' => '02/12/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.13.2026.pdf',
            //     'date' => '02/13/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.14.2026.pdf',
            //     'date' => '02/14/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.15.2026.pdf',
            //     'date' => '02/15/2026',
            // ],
            // [
            //     'name' => 'app/NOC DDR Feb.17.2026.pdf',
            //     'date' => '02/17/2026',
            // ],
            [
                'name' => 'app/NOC DDR Feb.18.2026.pdf',
                'date' => '02/18/2026',
            ],
            [
                'name' => 'app/NOC DDR Feb.19.2026.pdf',
                'date' => '02/19/2026',
            ],


        ];



        // return $this->logData($WAHA_fileNames, WAHAWellExtractor::class);
        // $run = new WAHAExtractionDump();
        // return $run->runExtraction($WAHA_fileNames);


        // return $this->logData($SOC_fileNames, SOCWellExtractor::class);
        // $run = new SOCExtractionDump();
        // return $run->runExtraction($SOC_fileNames);


        // return $this->logData($AGOCO_fileNames, AGOCOWellExtractor::class);
        // $run = new AGOCOExtractionDump();
        // return $run->runExtraction($AGOCO_fileNames);


        return $this->logData($AOO_fileNames, AOOWellExtractor::class);
        $run = new AOOExtractionDump();
        return $run->runExtraction($AOO_fileNames);

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

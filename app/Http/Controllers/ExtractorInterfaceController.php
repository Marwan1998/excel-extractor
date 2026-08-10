<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExtractorInterfaceRequest;
use Flash;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Drilling - WAHA
use App\Services\Excel\WAHAWellExtractor;
use App\Services\RunExtraction\WAHAExtractionDump;
//
use App\Services\Pdf\WAHAWellExtractorPDF;
use App\Services\RunExtraction\WAHAExtractionDumpPDF;
// Drilling - SOC
use App\Services\Word\SOCWellExtractor;
use App\Services\RunExtraction\SOCExtractionDump;
// Drilling - AGOCO
use App\Services\Pdf\AGOCOWellExtractor;
use App\Services\RunExtraction\AGOCOExtractionDump;
// Drilling - AOO
use App\Services\Pdf\AOOWellExtractor;
use App\Services\RunExtraction\AOOExtractionDump;



// Workover - WAHA
use App\Services\RunExtraction\Workover\WAHAExtractionDumpWorkover;
use App\Services\Pdf\WAHAWellExtractorWorkover;
// Workover - SOC
use App\Services\Word\SOCWellExtractorWorkover;
use App\Services\RunExtraction\Workover\SOCExtractionDumpWorkover;
// Workover - AGOCO
use App\Services\Pdf\AGOCOWellExtractorWorkover;
use App\Services\RunExtraction\Workover\AGOCOExtractionDumpWorkover;
// Workover - AOO
use App\Services\Pdf\AOOWellExtractorWorkover;
use App\Services\RunExtraction\Workover\AOOExtractionDumpWorkover;


class ExtractorInterfaceController extends AppBaseController
{
    public function create()
    {
        $companies = [null => 'Please Select', 'soc' => 'Sirte Oil Company', 'agoco' => 'AGOCO', 'waha' => 'WAHA Oil Company', 'aoo' => 'Akakus', 'waha_xlsx' => 'WAHA Oil Company - Excel converted'];

        return view('extractor_interfaces.create')->with('companies', $companies);
    }

    public function store(CreateExtractorInterfaceRequest $request)
    {
        $input = $request->all();

        $file = $input['file_name'];
        $path = $file->store('temp_uploads');
        $fullPath = 'app/' . $path;

        try {
            if($input['report_type'] == 'drilling'){
                switch ($input['company_name']) {
                    case 'soc':
                        $run = new SOCExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'agoco':
                        $run = new AGOCOExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'waha':
                        $run = new WAHAExtractionDumpPDF();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'waha_xlsx':
                        $run = new WAHAExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'aoo':
                        $run = new AOOExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    default:
                        return 'wrong company name selected';
                        break;
                }

            } else { //workover
                switch ($input['company_name']) {
                    case 'soc':
                        $run = new SOCExtractionDumpWorkover();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        break;

                    case 'agoco':
                        logd('hit agoco case');
                        $run = new AGOCOExtractionDumpWorkover();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        break;

                    case 'waha':
                        $run = new WAHAExtractionDumpWorkover();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        break;

                    case 'aoo':
                        $run = new AOOExtractionDumpWorkover();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date']]]);
                        break;

                    default:
                        return 'wrong company name selected';
                        break;
                }
            }

        } catch (\Throwable $th) {
            logd([$th->getMessage(), 'ExtractorInterfaceController.store']);
            return ['An error occurred, data was not inserted or inserted missy', $th->getMessage()];
        }

        return ['Data inserted: ', $dataInserted];
        // return redirect(route('extractorInterfaces.index'));
    }

    public function validateReportData(Request $request)
    {
        $input = $request->all();

        $file = $input['file_name'];
        $path = $file->store('temp_uploads');
        $fullPath = 'app/' . $path;


        try {
            if($input['report_type'] == 'drilling'){
                // Drilling
                switch ($input['company_name']) {
                    case 'soc':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], SOCWellExtractor::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'agoco':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], AGOCOWellExtractor::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'waha':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], WAHAWellExtractorPDF::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'waha_xlsx':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], WAHAWellExtractor::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'aoo':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], AOOWellExtractor::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    default:
                        $data = 'Wrong company name selected';
                        break;
                }

            } else {
                // Workover
                switch ($input['company_name']) {
                    case 'soc':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], SOCWellExtractorWorkover::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'agoco':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], AGOCOWellExtractorWorkover::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'waha':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], WAHAWellExtractorWorkover::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'aoo':
                        $data = logData([['name' => $fullPath, 'date' => $input['date'], ]], AOOWellExtractorWorkover::class);   
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    default:
                        return 'Wrong company name selected';
                        break;
                }
            }

            $data = ['status' => 'success', 'results' => $data];
        } catch (\Throwable $th) {
            logd([$th->getMessage(), 'ExtractorInterfaceController.validateReportData']);
            $data = ['status' => 'error', 'results' => [$th->getMessage()]];
        }

        return response()->json($data);
    }



    public function downloadSingle($name)
    {
        $path = storage_path('app/' . $name);
        if (file_exists($path)) {
            return response()->download($path);
        }
        abort(404, "File $name not found");
    }

    public function emptyDDRDWRFiles(Request $request)
    {
        $fileNames = ['DDR.xlsx', 'DWR.xlsx'];
        
        foreach ($fileNames as $fileName) {
            $filePath = storage_path('app/' . $fileName);

            if (file_exists($filePath)) {
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                // Find last used row
                $highestRow = $sheet->getHighestRow();

                // Clear data starting from Row 2 (keeping headers)
                if ($highestRow > 1) {
                    // If highestRow is 10, we remove from 3 to 10 (8 rows total)
                    $sheet->removeRow(3, $highestRow - 1);
                }

                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);
            }
        }

        return response()->json(['message' => 'DDR and DWR files have been reset.']);
    }


    public function downloadSingle2($name)
    {
        $path = storage_path('app/' . $name);
        if (file_exists($path)) {
            return response()->download($path);
        }
        abort(404, "File $name not found");
    }

}

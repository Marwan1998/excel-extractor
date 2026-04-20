<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExtractorInterfaceRequest;
use Flash;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;

// Drilling
use App\Services\Excel\WAHAWellExtractor;
use App\Services\RunExtraction\WAHAExtractionDump;
use App\Services\Word\SOCWellExtractor;
use App\Services\RunExtraction\SOCExtractionDump;
use App\Services\Pdf\AGOCOWellExtractor;
use App\Services\RunExtraction\AGOCOExtractionDump;
use App\Services\Pdf\AOOWellExtractor;
use App\Services\RunExtraction\AOOExtractionDump;

// Workover



class ExtractorInterfaceController extends AppBaseController
{
    public function create()
    {
        $companies = ['soc' => 'Sirte Oil Company', 'agoco' => 'AGOCO', 'waha' => 'WAHA Oil Company', 'aoo' => 'Akakus'];

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
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date'], ]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'agoco':
                        $run = new AGOCOExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date'], ]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'waha':
                        $run = new WAHAExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date'], ]]);
                        unlink(storage_path($fullPath)); // Delete temp file
                        break;

                    case 'aoo':
                        $run = new AOOExtractionDump();
                        $dataInserted = $run->runExtraction([['name' => $fullPath, 'date' => $input['date'], ]]);
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
                        $dataInserted = $run->runExtraction($input['file_name']);
                        break;

                    case 'agoco':
                        $run = new AGOCOExtractionDumpWorkover();
                        $dataInserted = $run->runExtraction($input['file_name']);
                        break;

                    case 'waha':
                        $run = new WAHAExtractionDumpWorkover();
                        $dataInserted = $run->runExtraction($input['file_name']);
                        break;

                    // case 'aoo':
                        // $run = new AOOExtractionDumpWorkover();
                        // $dataInserted = $run->runExtraction($input['file_name']);
                    //     break;

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

    public function cleanFile(Request $request)
    {
        //
    }

}

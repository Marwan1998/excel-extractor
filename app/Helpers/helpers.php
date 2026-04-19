<?php
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

if (!function_exists('getDateId')) {
    function getDateId($dateString)
    {
        // 1. Define the starting point
        $startDate = Carbon::createFromFormat('m/d/Y', '01/01/2026')->startOfDay();
        $startId = 366;

        // 2. Parse the incoming date (assuming m/d/Y format based on your examples)
        $inputDate = Carbon::createFromFormat('m/d/Y', $dateString)->startOfDay();

        // 3. Calculate the difference in days
        // diffInDays returns a positive integer representing the gap
        $diff = $startDate->diffInDays($inputDate);

        // 4. Return the calculated ID
        return $startId + (int)$diff;
    }
}

if (!function_exists('logd')) {
    function logd($data, $where = '-')
    {
        if(is_array($data)){
            \Log::debug($where, $data);
        } else {
            \Log::debug($where, [$data]);
        }

        /* TODO: Check Those

            Log::error('Hello');
            Log::emergency('Hello');
            Log::alert('Hello');
            Log::critical('Hello');
            Log::error('Hello');
            Log::warning('Hello');
            Log::notice('Hello');
            Log::info('Hello');
            Log::debug('Hello');
        */  
    }
}

if (!function_exists('logData')) {
    function logData($fileNames, $extractor)
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

if (!function_exists('cleanNumericValue')) {
    function cleanNumericValue($value)
    {
        // Only reject real empties
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        // Extract first number (integer or decimal)
        if (preg_match('/\d+(\.\d+)?/', $value, $matches)) {
            return $matches[0];
        }

        return null;
    }
}

if (!function_exists('splitWellName')) {
    function splitWellName(string $value): array
    {
        $parts = preg_split('/\s+/', trim($value), 2);
        return [
            $parts[0] ?? $value,
            $parts[1] ?? null,
        ];
    }
}

if (!function_exists('calculateSpudDate')) {
    function calculateSpudDate($date, $days)
    {
        if(empty($date) || empty($days) || $days == 0 || !is_numeric($days)){
            return '';
        }

        $date = Carbon::createFromFormat('m/d/Y', $date);

        // Subtract the desired number of days
        $newDate = $date->copy()->subDays($days)->format('m/d/Y');

        return $newDate; // Output: 22/12/2025
    }
}

if (!function_exists('getExcelDateFormat')) {
    function getExcelDateFormat($dateValue)
    {
        if (empty($dateValue)) {
            return;
        }

        // Convert string / Carbon / DateTime → DateTime
        $date = \Carbon\Carbon::parse($dateValue);

        // Convert to Excel serial number
        return ExcelDate::PHPToExcel($date);
    }
}

if (!function_exists('extractDateFromFileName')) {
    function extractDateFromFileName(string $filename)
    {
        // Remove extension to avoid confusion with numbers in .xlsx or .docx
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        // 1. Try to catch standard numeric patterns: DD-MM-YYYY, DD.MM.YYYY, DD/MM/YYYY, or D-M-YYYY
        // Matches: 01-03-2026, 1-4-2026, 19.03.2026
        if (preg_match('/(\d{1,2})[\-\.\/](\d{1,2})[\-\.\/](\d{4})/', $nameWithoutExt, $matches)) {
            try {
                // Note: In your list, these look like Day-Month-Year (e.g., 19.03)
                return Carbon::createFromFormat('d-m-Y', "{$matches[1]}-{$matches[2]}-{$matches[3]}")
                            ->format('m/d/Y');
            } catch (\Exception $e) {
                // Fallback if Day/Month were swapped in the filename
            }
        }

        // 2. Try to catch Month Name patterns: "MAR-14-2026", "Jan. 01, 2026", "Mar.02.2026"
        // Regex looks for: (Month Name) (Separator) (Day) (Separator) (Year)
        $monthRegex = '/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\s\.\-\_]*(\d{1,2})[\s\.\,\-\_]*(\d{4})/i';
        
        if (preg_match($monthRegex, $nameWithoutExt, $matches)) {
            try {
                $month = $matches[1];
                $day = $matches[2];
                $year = $matches[3];
                return Carbon::parse("{$month} {$day} {$year}")->format('m/d/Y');
            } catch (\Exception $e) {
                // Log error or ignore
            }
        }

        return null; // Or return a default value if no date is found
    }
}

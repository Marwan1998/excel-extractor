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

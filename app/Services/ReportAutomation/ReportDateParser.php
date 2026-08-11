<?php

namespace App\Services\ReportAutomation;

use DateTimeImmutable;

class ReportDateParser
{
    protected const MONTHS = [
        'jan' => 1,
        'feb' => 2,
        'mar' => 3,
        'apr' => 4,
        'may' => 5,
        'jun' => 6,
        'jul' => 7,
        'aug' => 8,
        'agu' => 8,
        'sep' => 9,
        'oct' => 10,
        'nov' => 11,
        'dec' => 12,
    ];

    public function parse(string $filePath): ?string
    {
        $name = pathinfo($filePath, PATHINFO_FILENAME);

        if (preg_match('/(?<!\d)(\d{1,2})[-._\/](\d{1,2})[-._\/](\d{4})(?!\d)/', $name, $match) === 1) {
            return $this->formatDate((int) $match[3], (int) $match[2], (int) $match[1]);
        }

        $monthPattern = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Agu|Sep|Oct|Nov|Dec)[a-z]*';

        if (preg_match('/'.$monthPattern.'[\s._-]*(\d{1,2})[\s,._-]*(\d{4})/i', $name, $match) === 1) {
            return $this->formatDate(
                (int) $match[3],
                $this->monthNumber($match[1]),
                (int) $match[2]
            );
        }

        if (preg_match('/(\d{1,2})[\s._-]*'.$monthPattern.'[\s,._-]*(\d{4})/i', $name, $match) === 1) {
            return $this->formatDate(
                (int) $match[3],
                $this->monthNumber($match[2]),
                (int) $match[1]
            );
        }

        return null;
    }

    protected function monthNumber(string $month): int
    {
        return self::MONTHS[strtolower(substr($month, 0, 3))] ?? 0;
    }

    protected function formatDate(int $year, int $month, int $day): ?string
    {
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return (new DateTimeImmutable())
            ->setDate($year, $month, $day)
            ->setTime(0, 0)
            ->format('m/d/Y');
    }
}

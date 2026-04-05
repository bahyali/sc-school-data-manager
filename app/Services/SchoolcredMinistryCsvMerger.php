<?php

namespace App\Services;

use InvalidArgumentException;
use SplFileObject;

class SchoolcredMinistryCsvMerger
{
    private const OUTPUT_HEADERS = [
        'School Name',
        'BSID',
        'OSSD',
        'School Type',
        'Grade Range',
        'Website',
        'OSSD Credits Offered',
        'M-School Level',
    ];

    /** @var array<string, string[]> */
    private const SCHOOLCRED_COLUMNS = [
        'school_name' => ['school name'],
        'bsid' => ['bsid'],
        'ossd' => ['ossd'],
        'school_type' => ['school type'],
        'grade_range' => ['grade range'],
    ];

    /** @var array<string, string[]> */
    private const MINISTRY_COLUMNS = [
        'bsid' => ['bsid'],
        'ossd_credits_offered' => ['ossd credits offered'],
        'website' => ['website'],
        'level' => ['level'],
    ];

    /**
     * @return array<int, array<int, string>>
     */
    public function merge(string $schoolcredPath, string $ministryPath): array
    {
        [$scHeaders, $scRows] = $this->readCsv($schoolcredPath);
        [$mHeaders, $mRows] = $this->readCsv($ministryPath);

        $scMap = $this->resolveColumnMap($scHeaders, self::SCHOOLCRED_COLUMNS, 'schoolcred');
        $mMap = $this->resolveColumnMap($mHeaders, self::MINISTRY_COLUMNS, 'ministry');

        $ministryByBsid = [];
        foreach ($mRows as $row) {
            $bsid = $this->normalizeBsid($row[$mMap['bsid']] ?? '');
            if ($bsid === '') {
                continue;
            }
            if (!isset($ministryByBsid[$bsid])) {
                $ministryByBsid[$bsid] = $row;
            }
        }

        $out = [];
        foreach ($scRows as $row) {
            $bsid = $this->normalizeBsid($row[$scMap['bsid']] ?? '');
            if ($bsid === '' || !isset($ministryByBsid[$bsid])) {
                continue;
            }
            $m = $ministryByBsid[$bsid];

            $out[] = [
                $row[$scMap['school_name']] ?? '',
                $row[$scMap['bsid']] ?? '',
                $row[$scMap['ossd']] ?? '',
                $row[$scMap['school_type']] ?? '',
                $row[$scMap['grade_range']] ?? '',
                $m[$mMap['website']] ?? '',
                $m[$mMap['ossd_credits_offered']] ?? '',
                $m[$mMap['level']] ?? '',
            ];
        }

        return $out;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>}
     */
    private function readCsv(string $path): array
    {
        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(',', '"', '');

        $headers = $file->fgetcsv();
        if ($headers === false || $headers === [null] || count(array_filter($headers, static function ($h) {
            return $h !== null && trim((string) $h) !== '';
        })) === 0) {
            throw new InvalidArgumentException('CSV has no header row.');
        }

        $headers = array_map(function ($h) {
            return $this->stripBom(trim((string) $h));
        }, $headers);

        $rows = [];
        while (!$file->eof()) {
            $row = $file->fgetcsv();
            if ($row === false || $row === [null]) {
                continue;
            }
            $row = array_map(static function ($cell) {
                return $cell === null ? '' : (string) $cell;
            }, $row);

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $name) {
                $assoc[$i] = $row[$i] ?? '';
            }
            $rows[] = $assoc;
        }

        return [$headers, $rows];
    }

    /**
     * @param array<int, string> $headers
     * @param array<string, string[]> $definition
     * @return array<string, int>
     */
    private function resolveColumnMap(array $headers, array $definition, string $fileLabel): array
    {
        $normToIndex = [];
        foreach ($headers as $i => $h) {
            $normToIndex[$this->normalizeHeaderLabel($h)] = $i;
        }

        $map = [];
        foreach ($definition as $key => $candidates) {
            $found = null;
            foreach ($candidates as $c) {
                $n = $this->normalizeHeaderLabel($c);
                if (isset($normToIndex[$n])) {
                    $found = $normToIndex[$n];
                    break;
                }
            }
            if ($found === null) {
                $expected = implode(', ', $candidates);
                throw new InvalidArgumentException(
                    "Missing required column in {$fileLabel} file (expected one of: {$expected})."
                );
            }
            $map[$key] = $found;
        }

        return $map;
    }

    private function normalizeHeaderLabel(string $h): string
    {
        $h = trim($h);
        if (strncmp($h, "\xEF\xBB\xBF", 3) === 0) {
            $h = substr($h, 3);
        }

        return strtolower(preg_replace('/\s+/', ' ', $h));
    }

    private function stripBom(string $str): string
    {
        if (strncmp($str, "\xEF\xBB\xBF", 3) === 0) {
            return substr($str, 3);
        }

        return $str;
    }

    private function normalizeBsid(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * @param array<int, string> $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public static function outputHeaderRow(): array
    {
        return self::OUTPUT_HEADERS;
    }
}

<?php

namespace App\Services;

use InvalidArgumentException;
use SplFileObject;

/**
 * Merges a primary CSV (e.g. Final Redacted) with a ministry CSV by BSID.
 * Output = all columns from the primary file (same order) + Website, OSSD Credits Offered, M-School Level.
 */
class FinalRedactedMinistryCsvMerger
{
    /** @var array<string, string[]> */
    private const MINISTRY_COLUMNS = [
        'bsid' => ['bsid'],
        'ossd_credits_offered' => ['ossd credits offered'],
        'website' => ['website'],
        'level' => ['level'],
    ];

    private const PRIMARY_BSID_CANDIDATES = ['bsid'];

    private const APPEND_HEADERS = [
        'Website',
        'OSSD Credits Offered',
        'M-School Level',
    ];

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>} [output headers, data rows]
     */
    public function merge(string $primaryPath, string $ministryPath): array
    {
        [$primaryHeaders, $primaryRows] = $this->readCsv($primaryPath);
        [$mHeaders, $mRows] = $this->readCsv($ministryPath);

        $primaryBsidIndex = $this->resolveColumnMap($primaryHeaders, ['bsid' => self::PRIMARY_BSID_CANDIDATES], 'primary')['bsid'];
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

        $outHeaders = array_merge($primaryHeaders, self::APPEND_HEADERS);
        $outRows = [];

        foreach ($primaryRows as $row) {
            $bsid = $this->normalizeBsid($row[$primaryBsidIndex] ?? '');
            if ($bsid === '' || !isset($ministryByBsid[$bsid])) {
                continue;
            }
            $m = $ministryByBsid[$bsid];

            $outRow = [];
            foreach ($primaryHeaders as $i => $_) {
                $outRow[] = $row[$i] ?? '';
            }
            $outRow[] = $m[$mMap['website']] ?? '';
            $outRow[] = $m[$mMap['ossd_credits_offered']] ?? '';
            $outRow[] = $m[$mMap['level']] ?? '';

            $outRows[] = $outRow;
        }

        return [$outHeaders, $outRows];
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
}

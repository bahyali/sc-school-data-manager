<?php

namespace App\Http\Controllers;

use App\Services\SchoolcredMinistryCsvMerger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolcredMinistryMergeController extends Controller
{
    public function form()
    {
        return view('schoolcred_ministry_merge');
    }

    /**
     * @return StreamedResponse|Response
     */
    public function merge(Request $request, SchoolcredMinistryCsvMerger $merger)
    {
        $request->validate([
            'schoolcred_file' => 'required|file|mimes:csv,txt|max:51200',
            'ministry_file' => 'required|file|mimes:csv,txt|max:51200',
        ]);

        $schoolcred = $request->file('schoolcred_file');
        $ministry = $request->file('ministry_file');

        try {
            $rows = $merger->merge($schoolcred->getRealPath(), $ministry->getRealPath());
        } catch (\InvalidArgumentException $e) {
            return response($e->getMessage(), 422);
        }

        $filename = 'merged_schoolcred_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, SchoolcredMinistryCsvMerger::outputHeaderRow());
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

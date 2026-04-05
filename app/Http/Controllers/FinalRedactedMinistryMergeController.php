<?php

namespace App\Http\Controllers;

use App\Services\FinalRedactedMinistryCsvMerger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalRedactedMinistryMergeController extends Controller
{
    public function form()
    {
        return view('final_redacted_ministry_merge');
    }

    /**
     * @return StreamedResponse|Response
     */
    public function merge(Request $request, FinalRedactedMinistryCsvMerger $merger)
    {
        $request->validate([
            'primary_file' => 'required|file|mimes:csv,txt|max:51200',
            'ministry_file' => 'required|file|mimes:csv,txt|max:51200',
        ]);

        $primary = $request->file('primary_file');
        $ministry = $request->file('ministry_file');

        try {
            [$headers, $rows] = $merger->merge($primary->getRealPath(), $ministry->getRealPath());
        } catch (\InvalidArgumentException $e) {
            return response($e->getMessage(), 422);
        }

        $filename = 'merged_final_redacted_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

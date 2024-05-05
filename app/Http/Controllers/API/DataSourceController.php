<?php

namespace App\Http\Controllers\API;

use App\Models\DataSource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Log;
use App\Models\SchoolRevision;



class DataSourceController extends Controller
{


    public function index()
    {
        return DataSource::whereNotIn('resource', ['auto_mixer', 'conflict_fixed', 'old_resource'])->paginate();
    }


    public function show($idOrName)
    {
        $ds = DataSource::find($idOrName);
        if (!$ds)
            $ds = DataSource::where('name', $idOrName)->first();
        return $ds;
    }


    public function findByName($name)
    {
        return DataSource::where('name', $name)->first();
    }



    // public function getOntarioLogs()
    // {
    //     $allFiles = Storage::files('ontario/all');
    //     $changesFiles = Storage::files('ontario/changes');

    //     $files = array_merge($allFiles, $changesFiles);

    //     return response()->json(['files' => $files]);
    // }


    public function getOntarioFiles()
    {

        $changed_files = array_map('basename', Storage::disk('public')->files('ontario/changes'));
        $unchanged_files = array_map('basename', Storage::disk('public')->files('ontario'));
        $all_files = [];

        foreach ($changed_files as $file) {
            $all_files[] = [
                'name' => $file,
                'path' => url('public' . Storage::url('ontario/changes/'.$file)),
                'changed' => true,
                'created_at' => Storage::disk('public')->lastModified('ontario/changes/'.$file),
            ];

        }


        // Add files from "all" folder that don't have the same name as any file in "changes" folder
        foreach ($unchanged_files as $file) {
            if(!collect($all_files)->contains('name', $file)){
                $all_files[] = [
                    'name' => $file,
                    'path' => url('public' . Storage::url('ontario/'.$file)),
                    'changed' => false,
                    'created_at' => Storage::disk('public')->lastModified('ontario/'.$file),

                ];
            }
        }

        // return $all_files;
        //to sort with month
        $all_files = collect($all_files)->sortByDesc(function ($file) {
            return $file['created_at'];
        })->values()->all();

        return response()->json(['data' => $all_files]);
    }



     public function getOntarioFileLogs($file_name)
     {
        $logs = Log::where('resource', $file_name)->with('getRevision')->get();

        return response()->json(['data' => $logs]);

     }



     public function getOntarioFileSingleLog(Log $log)
     {
        $log_revision = $log->revision;
        $differences = [];


        if($log->effect == 'change'){

            $the_revision_before = SchoolRevision::where('school_id', $log_revision->school_id)
                ->where('data_source_id', $log_revision->data_source_id)
                ->where('created_at', '<', $log_revision->created_at)
                ->latest()
                ->first();

            $excluded_keys = ['id', 'created_at', 'updated_at', 'hash'];
            
            foreach (collect($log_revision) as $key => $value) {
                if (!in_array($key, $excluded_keys) && $value != $the_revision_before[$key]) {

                    $differences[$key] = [
                        'before' => $the_revision_before[$key],
                        'after' => $value,
                    ];
                }
            }

        }

        return response()->json(['log' => $log, 'school' => $log_revision->school, 'differences' => $differences]);
     }



     //for the frontend
     public function getOntarioLogs($school_id = null)
     {
        ini_set('max_execution_time', 300);

        $arr = [];
        // $logs = Log::with('revision.school')->get();


        // foreach ($logs as $log) {
        //     return $log->revision->name;
        // }




        // $logs = Log::with(['revision', 'school'])->limit(10)->latest()->get();
        if($school_id) $logs = Log::with(['revision', 'school'])->where('school_id', $school_id)->get();
        else $logs = Log::with(['revision', 'school'])->get();

        foreach ($logs as $log) {
            $log_revision = $log->revision;
            $school = $log->school;
            $differences = [];
            
            if($log->effect == 'change'){

                $the_revision_before = SchoolRevision::where('school_id', $school->id)
                    ->where('data_source_id', $log_revision->data_source_id)
                    ->where('created_at', '<', $log_revision->created_at)
                    ->latest()
                    ->first();

                $excluded_keys = ['id', 'created_at', 'updated_at', 'hash', 'school', 'ossd_credits_offered', 'address_line_3', 'suite'];
                
                foreach (collect($log_revision) as $key => $value) {
                    // if (!in_array($key, $excluded_keys) && $value != $the_revision_before[$key]) {
                    if (!in_array($key, $excluded_keys) && $value != $the_revision_before[$key] && $value !== null && $the_revision_before[$key] !== null) {    

                        // Normalize both current and previous values for comparison
                        $normalized_current_value = $this->normalize($value);
                        $normalized_before_value = $this->normalize($the_revision_before[$key]);


                        if ($normalized_current_value !== $normalized_before_value) {
                            $differences[$key] = [
                                'before' => $the_revision_before[$key],
                                'after' => $value,
                            ];
                        }
                    }
                }

            }


            if($log->effect == 'change' & !$differences ) continue;

            $modified = [
                // 'id' => $log->id,
                'effect' => $log->effect,
                // 'status' => $log->resource,
                'date' => $this->extractYearMonth($log),
                'differences' => $differences,
                'school_name' => $school->name,
                'number' => $school->number,
                'ossd' => $log_revision->ossd_credits_offered,
                'program_type' => $log_revision->program_type,
                'year' => $this->extractYearMonth($log, true),
                'id' => $log->id,
            ];

            $arr[] = $modified;

        }

        return response()->json(['data' => $arr]);
     }



    public function extractYearMonth($log, $year_only = false)
    {
        $matches = [];
        $matches_two = [];
        preg_match('/_(january|february|march|april|may|june|july|august|september|october|november|december|\d{2})(\d{4})_/i', $log->resource, $matches);

        preg_match('/_(january|february|march|april|may|june|july|august|september|october|november|december|\d{1,2})_(\d{4})_/i', $log->resource, $matches_two);


        if (count($matches) === 3) {
            if (is_numeric($matches[1])) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            } else {
                $month = str_pad(date('m', strtotime(ucfirst($matches[1]))), 2, '0', STR_PAD_LEFT);
            }
            $year = $matches[2];
            return ($year_only) ? $year : $year . '-' . $month ;
        }


        if (count($matches_two) === 3) {
            if (is_numeric($matches_two[1])) {
                $month = str_pad($matches_two[1], 2, '0', STR_PAD_LEFT);
            } else {
                $month = str_pad(date('m', strtotime(ucfirst($matches_two[1]))), 2, '0', STR_PAD_LEFT);
            }
            $year = $matches_two[2];
            return ($year_only) ? $year : $year . '-' . $month ;

        }
        
        //in case can not match
        return ($year_only) ? $log->created_at->format('Y') : $log->created_at->format('Y-m'); 
    }




    // Function to normalize address strings
    public function normalize($string) {

        // Remove common punctuation and space
        $string = str_replace([',', '.', ';', ':', '-', '_', ' '], '', $string);
        // Remove extra spaces
        $string = preg_replace('/\s+/', ' ', $string);
        // Convert to lowercase for case-insensitive comparison
        $string = strtolower($string);
        
        return $string;
    }



    
}



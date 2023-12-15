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



    public function store(Request $request)
    {
        //
    }



    public function show($idOrName)
    {
        $ds = DataSource::find($idOrName);
        if (!$ds)
            $ds = DataSource::where('name', $idOrName)->first();
        return $ds;
    }


    public function update(Request $request, DataSource $dataSource)
    {
        //
    }


    public function destroy(DataSource $dataSource)
    {
        //
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
        $unchanged_files = Storage::disk('public')->files('ontario/all');
        $changed_files = Storage::disk('public')->files('ontario/changes');
        $all_files = [];

        foreach ($changed_files as $file) {

            //convert name to this format 'ontario_month_year' to compare files inside all folder
            $name_without_the_day = str_replace('ontario/changes/', '', preg_replace('/(\d{2})_(\d{2})_(\d{4})/', '$2_$3', $file));
            $all_files[] = [
                'name' => $name_without_the_day,
                // 'path' => Storage::url($file),
                'path' => url('public' . Storage::url($file)),
                'changed' => true,
                'original_name' => str_replace('ontario/changes/', '', $file)//to search database!
            ];

        }


        // Add files from "all" folder that don't have the same month as any file in "changes" folder
        foreach ($unchanged_files as $file) {
            $file_name = str_replace('ontario/all/', '', $file);

            if(!collect($all_files)->contains('name', $file_name)){
                $all_files[] = ['name' => $file_name, 'path' => url('public' . Storage::url($file)), 'changed' => false, 'original_name' => $file_name];
            }
        }

        //to sort with month
        $all_files = collect($all_files)->sortBy(function ($file) {
            return $file['name'];
        })
        ->values()
        ->all();;

        return response()->json(['data' => $all_files]);
    }



     public function getOntarioFileLogs($file_name)
     {
        $logs = Log::where('resource', $file_name)->get();
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

    
}


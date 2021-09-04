<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataSource;
use App\Imports\SchoolsExcelMapperImport;
use Maatwebsite\Excel\Facades\Excel;

// use App\Models\Crawler;
// use Spatie\Crawler\Crawler;
use App\Observer;
use Symfony\Component\DomCrawler\Crawler;
use App\Classes\ScrapingRevokedSchool;
use App\Classes\ScrapingClosedSchool;
use App\Models\School;
use App\Models\SchoolRevision;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class ImporterController extends Controller
{


    public function excelImporting(Request $request){

     	$data_source = DataSource::where('name', $request->data_src_name)->first();

	    $file_checksum = md5_file(request()->file('schools_file'));

     	$data_source->update(['last_sync' => Carbon::now()]);

     	if($data_source->checksum == $file_checksum ) return 'this file uploaded already!';
     	else $data_source->update(['checksum' => $file_checksum]);
     	// return $request->school_status;
     	
     	$this->importFromExcel($data_source, $request->file('schools_file'), $request->school_status);
    	return'done';
    }


    public function importFromExcel($data_source, $file, $status){
	    return Excel::import(new SchoolsExcelMapperImport($data_source, $status), $file);

    }




    public function importFromCrawler($data_source)
    {
    }




    public function storeRevokedSchools()
    {

     	// return$data_source = DataSource::where('name', 'revoked_schools')->first();

		$revoked_school = new ScrapingRevokedSchool;
    	$revoked_school = $revoked_school->start();
    	return'done';

    }



    public function storeClosedSchools()
    {

		$closed_school = new ScrapingClosedSchool;
    	$closed_school = $closed_school->start();
    	return'done';

    } 





}


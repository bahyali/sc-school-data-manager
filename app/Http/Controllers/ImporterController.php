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


class ImporterController extends Controller
{
    public function importing(Request $request){

     	$data_source = DataSource::where('name', $request->school_status)->first(); 

     	if ($data_source->resource == 'excel') {
     		$this->importFromExcel($data_source, $request->file('schools_file'));
     	}

	  	return 'done!';
    	  

    }


    public function importFromExcel($data_source, $file){
	    return Excel::import(new SchoolsExcelMapperImport($data_source), $file);

    }




    public function importFromCrawler($data_source)
    {
    }




    public function storeRevokedSchools()
    {

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


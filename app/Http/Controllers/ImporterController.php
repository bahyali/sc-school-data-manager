<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataSource;
use App\Imports\FirstSheetImporter;
use App\Classes\ScrapingRevokedSchool;
use App\Classes\ScrapingClosedSchool;
use App\Classes\SchoolRecord;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use App\Models\SchoolRevision;
use DB;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SchoolsExcelMapperImportMulti;
use App\Models\DataChange;
// use Symfony\Component\DomCrawler\Crawler;



class ImporterController extends Controller
{

	public function excelImporting(Request $request)
	{

		$data_source = DataSource::where('name', $request->data_src_name)
			->first();

		$response = $this->importFromExcel($data_source, $request->file('schools_file'));

		return $response;
	}


	public function importFromExcel($data_source, $file)
	{
		ini_set('max_execution_time', 10000); //10 minutes
		ini_set('memory_limit', '1024M'); // Set memory limit to 1 GB

		// Force excel to take only first sheet temporarily.
		// TODO Handle multiple sheet definitions

		if (!$data_source->active) return 'Data Source is deactivated!';


		$file_checksum = md5_file($file);

		if ($data_source->checksum !== $file_checksum) {
			 (new FirstSheetImporter($data_source))->import($file);

    	 	// Excel::import(new SchoolsExcelMapperImportMulti($data_source), $file);

			 $response = 'File was uploaded & processed successfully!';
			 $data_source->update([
				'last_sync' => Carbon::now(),
				'checksum' => $file_checksum
			]);

		} else {
			$response = 'This file was uploaded before!';
			$data_source->touch();
		}

		// If imported successfully update metadata
		

		return $response;
	}


	public function remixAllSchools()
	{
		foreach (School::lazy() as $school) {
			$school_record = App::make(SchoolRecord::class);
			$school_record->setSchool($school);

			$school_record->remix();
		}
		return "done";
	}


	public function crawlSchoolById($id)
	{
		$data_source = DataSource::findOrFail($id);

		$factory = [
			'revoked_schools' => ScrapingRevokedSchool::class,
			'closed_schools' => ScrapingClosedSchool::class
		];

		$ds_class = new $factory[$data_source->name]($data_source);

		$ds_class->start();

		// $data_source->update([
		// 	'last_sync' => Carbon::now()
		// ]);

		return 'Crawled Successfully!';
	}



	public function crawlSchoolsByName($ds_name)
	{
		
		ini_set('max_execution_time', 600); //10 minutes
	
		$factory = [
			'revoked' => [
				'class' => ScrapingRevokedSchool::class,
				'data_source' => DataSource::where('name', 'revoked_schools')->first()
			],
			'closed' => [
				'class' => ScrapingClosedSchool::class,
				'data_source' => DataSource::where('name', 'closed_schools')->first()
			]
		];

		if ($factory[$ds_name]['data_source']->active) {
			$ds_class = new $factory[$ds_name]['class']($factory[$ds_name]['data_source']);

			DB::beginTransaction();
			$ds_class->start();
			DB::commit();


			// $factory[$ds_name]['data_source']->update([
			// 	'last_sync' => Carbon::now()
			// ]);

			return 'Crawled Successfully!';
		} 

		else {
			return 'Data Source is deactivated!';
		}
	}




	public function ontarioImporting()
	{
		$context = stream_context_create(
		    array(
		        "http" => array(
		            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
		        )
		    )
		);
		
		$data_source = DataSource::where('name', 'active_schools')->first();
		$url = $data_source->configuration['url'];

		$html_url = $data_source->url;
		$html_page_content = file_get_contents($html_url, false, $context);

    	// preg_match('/<a\s+class="dataset-download-link resource-url-analytics resource-type-None"\s+href="([^"]+)"/i', $html_page_content, $matches);
    	preg_match('/<a\s+class="[^"]*dataset-download-link[^"]*"\s+href="([^"]+)"/i', $html_page_content, $matches);

		if (!empty($matches[1])) {
	      	$href = $matches[1];
		 	$file_name = pathinfo($href, PATHINFO_BASENAME);
			$file = file_get_contents($href, false, $context);
		} else {
		    return "Link not found.";
		}


		$filePath = 'ontario/'.$file_name;

		if (Storage::disk('public')->exists($filePath)) {
		    Storage::disk('public')->delete($filePath);
		}

		if (Storage::disk('public')->put($filePath, $file)){
			$path = storage_path('app/public/'.$filePath);
		}

		else
			throw new Exception('Couldn\'t save file!');


		return $this->importOntarioExcel($data_source, $path, $file_name);
	}



	public function importOntarioExcel($data_source, $file, $file_name)
	{

		$context = stream_context_create(
		    array(
		        "http" => array(
		            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
		        )
		    )
		);
		ini_set('max_execution_time', 1800);

		if (!$data_source->active) return 'Data Source is deactivated!';
		
		$file_checksum = md5_file($file);

		if ($data_source->checksum !== $file_checksum) {

			$file_content = file_get_contents($file, false, $context);

			$filePath = 'ontario/changes/'.$file_name;

			if (Storage::disk('public')->exists($filePath)) {
			    Storage::disk('public')->delete($filePath);
			}


			Storage::disk('public')->put($filePath, $file_content);

			$new_configuration = $data_source->configuration;
			$new_configuration['file_name'] = $file_name;
			$data_source->configuration = $new_configuration;
		 	$data_source->save();

		 	(new FirstSheetImporter($data_source))->import($file);

		 	$data_source->update([
				'last_sync' => Carbon::now(),
				'checksum' => $file_checksum
			]);

		 	$response = 'File was uploaded & processed successfully!';

		} else {
			$response = 'This file was uploaded before!';
			$data_source->touch();
		}
		return $response;
	}




	//Temp func
	public function principals(){

		 $revisions = SchoolRevision::where('created_at', '>=', '2022-10-04')->whereIn('data_source_id', [2,5])->get();


		 $arr = [];
		 foreach($revisions as $key => $rev){

		 	if($rev->principal_name && $rev->principal_last_name && !str_contains(strtolower($rev->principal_name), strtolower($rev->principal_last_name)) ){

		 		$rev->principal_name = $rev->principal_name.' '.$rev->principal_last_name;
		 		$rev->touch();
		 		$rev->school->touch();
		 		$rev->save();
		 		$arr[] = $rev->principal_name.' '.$rev->principal_last_name;

		 	}
		 }

		 return $arr;

	}



	public function schoolType(){


		$data_source = DataSource::where('name', 'schoolcred_engine')->first();

	 	$revisions = SchoolRevision::whereIn('type', ['private inspected', 'Private Inspected'])->where('data_source_id', $data_source->id)->get();

	 	foreach($revisions as $key => $rev){

		 	if($rev->ossd_credits_offered && strtolower($rev->ossd_credits_offered) != 'no' ){}

	 		else{

		 		$rev->ossd_credits_offered = 'yes';
		 		$rev->touch();
		 		$rev->school->touch();
		 		$rev->save();

		 	}
		 }

		 return 'done';

	}



	// public function testRecord(){

	// 	// return 'asdasd';
	// 	$record = App::make(SchoolRecord::class);
	// 	$data_source = DataSource::find(1);
    //     $array['data_source_id'] = $data_source->id;
    //     $array['status'] = 'active';
    //     $array['name'] = 'testdummmmmmmmy';
    //     $array['number'] = '1994419';
    //     $array['principal_name'] = 'newwasdadasdwwww';
    //     $array['special_conditions_code'] = 'true';
    //     $array['address_line_1'] = 'newasdasdasdasdasdasdwwww';
    //     $array['address_line_2'] = 'new';
    //     $array['address_line_3'] = '52';
    //     $array['country'] = '54';
    //     $array['telephone'] = '52';
    //     $array['teachers_num'] = 20;
    //     $array['oct_teachers'] = 11;
    //     $array['fax'] = '123';

    //     $school = $record->addSchool($array['number']);
    //     $school->addRevision($array, $data_source, true, false, false, false, false);
    //     return'done';
	// }



	public function mergeStatus(){

		$changes = DataChange::where('column','status')->get();

		foreach ($changes as $change) {
		 	$revoked_data_row = SchoolRevision::where('school_id', $change->school_id)->where('revoked_date', '!=', NULL)->latest()->first();
        	$closed_date_row = SchoolRevision::where('school_id', $change->school_id)->where('closed_date', '!=', NULL)->latest()->first();

        	if($revoked_data_row) {
			$change->school->lastRevision->revoked_date = $revoked_data_row->revoked_date;
        	}

        	if($closed_date_row) {
			$change->school->lastRevision->closed_date = $closed_date_row->closed_date;
        	}

	 		$change->school->lastRevision->save();
        	$change->school->lastRevision->touch();
        	$change->school->touch();

		}
			return 'done';
	}




// public function crawlSchoolById($id)
// 	{
// 		$data_source = DataSource::findOrFail($id);

// 		$factory = [
// 			'revoked_schools' => ScrapingRevokedSchool::class,
// 			'closed_schools' => ScrapingClosedSchool::class
// 		];

// 		$ds_class = new $factory[$data_source->name]($data_source);

// 		$ds_class->start();

// 		// $data_source->update([
// 		// 	'last_sync' => Carbon::now()
// 		// ]);

// 		return 'Crawled Successfully!';
// 	}




	public function maintainOpenDate(){

		$startDate = Carbon::create(2024, 1, 1, 0, 0, 0);

		$dates = [];
		$schools = School::where('created_at', '>', $startDate)->get();

		foreach ($schools as $school) {
			if(!$school->lastRevision->open_date)
				$dates[] = 'yes';
			else
				$dates[] = 'no';
		}
		
        return $dates;
	}


}

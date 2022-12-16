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
		ini_set('max_execution_time', 1800); //10 minutes
		// Force excel to take only first sheet temporarily.
		// TODO Handle multiple sheet definitions

		if (!$data_source->active) return 'Data Source is deactivated!';


		$file_checksum = md5_file($file);

		if ($data_source->checksum !== $file_checksum) {
			 (new FirstSheetImporter($data_source))->import($file);

    	 	// Excel::import(new SchoolsExcelMapperImportMulti($data_source), $file);

			 $response = 'File was uploaded & processed successfully!';
		} else {
			$response = 'This file was uploaded before!';
		}

		// If imported successfully update metadata
		$data_source->update([
			'last_sync' => Carbon::now(),
			'checksum' => $file_checksum
		]);

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

		$data_source->update([
			'last_sync' => Carbon::now()
		]);

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
			$ds_class->start();


			$factory[$ds_name]['data_source']->update([
				'last_sync' => Carbon::now()
			]);

			return 'Crawled Successfully!';
		} 

		else {
			return 'Data Source is deactivated!';
		}
	}



	// public function storeRevokedSchools()
	// {
	// 	$data_source = DataSource::where('name', 'revoked_schools')
	// 		->first();
	// 	$revoked_school = new ScrapingRevokedSchool($data_source);
	// 	$revoked_school->start();

	// 	return 'done';
	// }



	// public function storeClosedSchools()
	// {
	// 	$data_source = DataSource::where('name', 'closed_schools')
	// 		->first();
	// 	$closed_school = new ScrapingClosedSchool($data_source);
	// 	$closed_school->start();

	// 	return 'done';
	// }


	public function ontarioImporting()
	{

		$data_source = DataSource::where('name', 'active_schools')->first();
		$url = $data_source->configuration['url'];
		$file = file_get_contents($url);

		if (Storage::disk('local')->put('ontario.xlsx', $file))
			$path = storage_path('app/ontario.xlsx');
		else
			throw new Exception('Couldn\'t save file!');

		return $this->importFromExcel($data_source, $path);
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



	public function testRecord(){

		// [44,45,46,47];
		$record = App::make(SchoolRecord::class);
		$data_source = DataSource::find(1);
        $array['data_source_id'] = $data_source->id;
        $array['status'] = 'closed';
        $array['name'] = 'lalalall';
        $array['number'] = '123456';
        $array['principal_name'] = 'lalalall';
        $array['special_conditions_code'] = 'true';
        $array['address_line_1'] = 'lalalall';
        $array['address_line_2'] = 'lalalall';
        $array['address_line_3'] = '555555555555555555';
        $array['country'] = '555555555555555555';
        $array['telephone'] = '555555555555555555';
        // $array['teachers_num'] = 20;
        // $array['oct_teachers'] = 11;
        $array['fax'] = 'closed';

        $school = $record->addSchool($array['number']);
        $school->addRevision($array, $data_source, false, false, false, false, true);
        return'done';
	}



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
}

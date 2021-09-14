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
use Symfony\Component\DomCrawler\Crawler;
use Storage;
use File;
use Response;


use Illuminate\Support\Facades\App;

class ImporterController extends Controller
{

	public function excelImporting(Request $request)
	{

		$data_source = DataSource::where('name', $request->data_src_name)
			->first();

		$this->importFromExcel($data_source, $request->file('schools_file'));
		return 'done';
	}


	public function importFromExcel($data_source, $file)
	{
		// Force excel to take only first sheet temporarily.
		// TODO Handle multiple sheet definitions
		$file_checksum = md5_file($file);

		if (!$data_source->checksum == $file_checksum){
			$import = (new FirstSheetImporter($data_source))->import($file);
			// return $import;
		}


		// If imported successfully update metadata
		$data_source->update([
			'last_sync' => Carbon::now(),
			'checksum' => $file_checksum
		]);


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




	public function storeRevokedSchools()
	{
		$data_source = DataSource::where('name', 'revoked_schools')
			->first();
		$revoked_school = new ScrapingRevokedSchool($data_source);
		$revoked_school->start();

		return 'done';
	}



	public function storeClosedSchools()
	{
		$data_source = DataSource::where('name', 'closed_schools')
			->first();
		$closed_school = new ScrapingClosedSchool($data_source);
		$closed_school->start();

		return 'done';
	}


	public function ontarioImporting()
	{

		$data_source = DataSource::where('name', 'private_schools_ontario')->first();
		$url = $data_source['configuration']['url'];
		$file = file_get_contents($url);
		Storage::disk('local')->put('ontario.xlsx', $file);
		$path = storage_path('app/ontario.xlsx');

		$this->importFromExcel($data_source, $path);
		return 'done';


	}



}


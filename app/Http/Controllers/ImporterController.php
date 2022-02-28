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
		ini_set('max_execution_time', 600); //10 minutes
		// Force excel to take only first sheet temporarily.
		// TODO Handle multiple sheet definitions

		if (!$data_source->active) return 'Data Source is deactivated!';


		$file_checksum = md5_file($file);

		if ($data_source->checksum !== $file_checksum) {
			(new FirstSheetImporter($data_source))->import($file);
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



	public function crawlSchools($ds_name)
	{

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

			return 'Done';
		} else {
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
}

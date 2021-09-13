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

use Illuminate\Support\Facades\App;

class ImporterController extends Controller
{

	public function excelImporting(Request $request)
	{

		$data_source = DataSource::where('name', $request->data_src_name)
			->first();

		$file_checksum = md5_file(request()->file('schools_file'));

		if (!$data_source->checksum == $file_checksum){
			// return 'This file was uploaded before!';
			$this->importFromExcel($data_source, $request->file('schools_file'));
		}

		// If imported successfully update metadata
		$data_source->update([
			'last_sync' => Carbon::now(),
			'checksum' => $file_checksum
		]);

		return 'done';
	}


	public function importFromExcel($data_source, $file)
	{
		// Force excel to take only first sheet temporarily.
		// TODO Handle multiple sheet definitions
		$import = (new FirstSheetImporter($data_source))->import($file);

		return $import;
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

		// return$data_source = DataSource::where('name', 'revoked_schools')->first();

		$data_source = DataSource::where('name', 'revoked_schools')
			->first();

		$revoked_school = new ScrapingRevokedSchool($data_source);

		$revoked_school->start();

		$data_source->update([
			'last_sync' => Carbon::now()
		]);


		return 'done';
	}



	public function storeClosedSchools()
	{
		$data_source = DataSource::where('name', 'closed_schools')
			->first();

		$closed_school = new ScrapingClosedSchool($data_source);

		$closed_school->start();

		$data_source->update([
			'last_sync' => Carbon::now()
		]);

		return 'done';
	}

	public function test()
	{


		// $url = 'https://data.ontario.ca/dataset/7a049187-cf29-4ffe-9028-235b95c61fa3/resource/6545c5ec-a5ce-411c-8ad5-d66363da8891/download/private_schools_contact_information_august_2021_en.xlsx';

		// $file = file_get_contents($url);
		// Storage::disk('local')->put('ontario.xlsx', $file);
$contents = Storage::get('ontario.xlsx');

$request = new \Illuminate\Http\Request();

$request->replace(['data_src_name' => 'private_schools_ontario', 'schools_file' => $contents]);

$file_checksum = md5_file($contents);
dd($request->all());

$this->excelImporting($request);
dd($request->schools_file);
		return'done';


		// return response()->download($tempImage, $filename);
		
		// $client = new \GuzzleHttp\Client();
		// $url = 'http://www.edu.gov.on.ca/eng/general/elemsec/privsch/revoked.html';
		// $res = $client->request('GET', $url);
		// $html = '' . $res->getBody();
		// $crawler = new Crawler($html);

		// $nodeValues = $crawler->filter('#right_column ol li');
		// $arr = [];
		// $nodeValues->each(function ($node) use (&$arr) {$arr[] = $node->html();});
		// $checksum = md5(json_encode($arr)); 
		// 	return $checksum;

	}


}


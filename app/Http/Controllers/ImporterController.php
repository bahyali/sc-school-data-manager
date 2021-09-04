<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataSource;
use App\Imports\FirstSheetImporter;
use Maatwebsite\Excel\Facades\Excel;
use App\Classes\ScrapingRevokedSchool;
use App\Classes\ScrapingClosedSchool;
use Carbon\Carbon;

class ImporterController extends Controller
{


	public function excelImporting(Request $request)
	{

		$data_source = DataSource::where('name', $request->data_src_name)->first();

		$file_checksum = md5_file(request()->file('schools_file'));

		$data_source->update(['last_sync' => Carbon::now()]);

		if ($data_source->checksum == $file_checksum)
			return 'This file was uploaded before!';


		// return $request->school_status;

		$this->importFromExcel($data_source, $request->file('schools_file'), $request->school_status);

		$data_source->update(['checksum' => $file_checksum]);

		return 'done';
	}


	public function importFromExcel($data_source, $file, $status)
	{
		// Force excel to take only first sheet temporarily.
		// TODO Handle multiple sheet definitions
		$import = (new FirstSheetImporter($data_source, $status))->import($file);

		return $import;
	}




	public function importFromCrawler($data_source)
	{
	}




	public function storeRevokedSchools()
	{

		// return$data_source = DataSource::where('name', 'revoked_schools')->first();

		$data_source = DataSource::where('name', 'revoked_schools')
			->first();

		$revoked_school = new ScrapingRevokedSchool($data_source);
		$revoked_school = $revoked_school->start();
		return 'done';
	}



	public function storeClosedSchools()
	{
		$data_source = DataSource::where('name', 'closed_schools')
			->first();

		$closed_school = new ScrapingClosedSchool($data_source);
		$closed_school = $closed_school->start();
		return 'done';
	}
}

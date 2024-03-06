<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataSource;
use App\Imports\TempFirstSheetImporter;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Exception;
use Maatwebsite\Excel\Facades\Excel;


class OntarioOldFilesImporterController extends Controller
{

	
	//Temp method to upload all old files
	public function importOntarioOldFiles(Request $request)
	{

		$context = stream_context_create(
		    array(
		        "http" => array(
		            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
		        )
		    )
		);
		$file_name =  $request->file('old_ontario_file')->getClientOriginalName();
		$file_content = file_get_contents($request->file('old_ontario_file'), false, $context);
		$data_source = DataSource::where('name', 'active_schools')->first();
		$filePath = 'ontario/'.$file_name;

		if (Storage::disk('public')->exists($filePath)) {
		    Storage::disk('public')->delete($filePath);
		}

		if (Storage::disk('public')->put($filePath, $file_content)){
			$path = storage_path('app/public/'.$filePath);
		}

		else
			throw new Exception('Couldn\'t save file!');


		return $this->tempImportOntarioExcel($data_source, $path, $file_name);
	}


	public function tempImportOntarioExcel($data_source, $file, $file_name)
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

		 	(new TempFirstSheetImporter($data_source))->import($file);


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



}





<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\DataSource;
use Carbon\Carbon;
use App\Models\SchoolRevision;
use Illuminate\Support\Facades\App;
use App\Classes\SchoolRecord;


class SchoolController extends Controller
{


	public function getSchools($status = NULL)
	{
		if ($status) $schools = School::where('status', $status)->with('getSchool')->get();
		else $schools = School::with('getSchool')->get();

		return response()->json($schools);
	}


	public function getSchoolsRevisions()
	{
		$schools = School::with('revisions')->get();
		return response()->json($schools);
	}


	public function getOneSchool($school_id)
	{
		$school = School::with(['revisions', 'revisions.dataSource'])->find($school_id);
		return response()->json($school);
	}


	public function getSchoolByDate(Request $request)
	{
		$date = $request->date ? Carbon::parse($request->date) : Carbon::parse('1990-04-14');

		$mixer_source = DataSource::where('name', 'schoolcred_engine')
			->first();

		// TODO move this inside the model
		return School::with(['revisions' => function ($query) use ($mixer_source, $date) {
			$query
				->where('data_source_id', $mixer_source->id)
				->where('created_at', '>=', $date)
				->latest();
				// ->take(1);
		}])
			->where('updated_at', '>=', $date)
			->get();
	}


	public function getConflictedSchools()
	{
		$conflicted_schools = School::where('conflict', true)->with('lastRevision')->get();
		return response()->json($conflicted_schools);

	}


	public function getSchoolConflictColumns($school_id, $column = null)
	{

		$search_columns = ['name', 'principal_name', 'address_line_1'];
		$search_columns = ($column) ? [$column] : $search_columns;
        $schoolcred_engine_ds = DataSource::where('name', 'schoolcred_engine')->first();


		$revs = SchoolRevision::select($search_columns)->where('school_id', $school_id)->where('data_source_id','!=', $schoolcred_engine_ds->id)->get();
		$arr = [];

		foreach ($revs as $rev) {

			foreach ($search_columns as $search_column) {
				if($rev->$search_column){
				 	$arr[$search_column] [] = trim($rev->$search_column);
					$arr[$search_column]  = array_unique(array_values($arr[$search_column]));
				}
			}
		}

		foreach ($arr as $key => $value) {
			if(count($value) == 1){unset($arr[$key]);}
		}
		return $arr;

		

	}


	public function FixConflict(Request $request)
	{
	
		$school = School::findOrFail($request->school_id);
		$fixed_revision =  $school->lastRevision()->first();
		$fixed_revision[$request->column_name] = $request->column_value;
		// return $fixed_revision->dataSource;

	 	$record = App::make(SchoolRecord::class);
        $school = $record->addSchool($school->number);
     	$school->addRevision($fixed_revision->toArray(), $fixed_revision->dataSource, false, true, false);
     	return'done';

	}
}

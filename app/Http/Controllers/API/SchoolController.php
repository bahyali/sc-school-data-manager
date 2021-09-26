<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\DataSource;
use Carbon\Carbon;

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
}

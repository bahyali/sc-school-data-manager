<?php

namespace App\Http\Controllers\API;

use App\Classes\ConflictFinder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\DataSource;
use Carbon\Carbon;
use App\Models\SchoolRevision;
use Illuminate\Support\Facades\App;
use App\Classes\SchoolRecord;
use App\Models\DataChange;
use App\Models\DataChangeValue;
use DB;
use Exception;

class SchoolController extends Controller
{


	public function getSchools($status = NULL)
	{
		if ($status) $schools = School::where('status', 'like', '%' . $status . '%')->with('getSchool')->get();
		// if ($status) $schools = School::where('status', $status)->with('getSchool')->get();
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
		$school = School::with(['lastRevision', 'revisions', 'revisions.dataSource'])->find($school_id);
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
				->where('updated_at', '>=', $date)
				->latest();
			// ->take(1);
		}])
			->where('updated_at', '>=', $date)
			->skip(1200)
			->take(408)
			->get();
	}


	public function getConflictedSchools()
	{
		$conflicted_schools = School::where('conflict', true)
			->with('lastRevision')
			->get();
		return response()->json(['conflicted_schools' => $conflicted_schools], 200);
	}


	public function getSchoolConflictColumns($change_id, $column = null)
	{

		$change = DataChange::with(['values', 'values.affectedRecord', 'values.affectedRecord.dataSource', 'affectedRecords'])->findOrFail($change_id);

		return response()->json(['data' => $change], 200);
	}

	public function getSchoolConflictColumnsx($school_id, $column = null)
	{

		$search_columns = ['name', 'principal_name', 'address_line_1'];
		$search_columns = ($column) ? [$column] : $search_columns;
		$schoolcred_engine_ds = DataSource::where('name', 'schoolcred_engine')->first();


		$revs = SchoolRevision::select($search_columns)->where('school_id', $school_id)->where('data_source_id', '!=', $schoolcred_engine_ds->id)->orderBy('updated_at', 'DESC')->take(2)->get();
		$school_conflicted_columns = [];

		foreach ($revs as $rev) {

			foreach ($search_columns as $search_column) {
				if ($rev->$search_column) {
					$school_conflicted_columns[$search_column][] = trim($rev->$search_column);
					$school_conflicted_columns[$search_column]  = array_unique(array_values($school_conflicted_columns[$search_column]));
				}
			}
		}

		foreach ($school_conflicted_columns as $key => $value) {
			if (count($value) == 1) {
				unset($school_conflicted_columns[$key]);
			}
		}

		return response()->json(['school_conflicted_columns' => $school_conflicted_columns], 200);
	}




	public function FixConflict(Request $request)
	{

		$conflict = DataChange::findOrFail($request->change_id);
		$ds = DataSource::where('name', 'conflict_fixed')->first();

		switch ($conflict->type) {
			case 'similarity':
			case 'change':
				$school = $conflict->getSchools()->first();
				$last_revision = $school->lastRevision()->first();
				$value_id = $request->value_id;

				if ($value_id != 'keep') {
					$value = DataChangeValue::findOrFail($value_id);

					$last_revision->{$conflict->column} = $value->affectedRecord->{$conflict->column};
					// $last_revision->touch();

					$record = App::make(SchoolRecord::class);
					$record->fetchSchool($school->id);

					if($conflict->column == 'status') {

						if($value->affectedRecord->{$conflict->column} == 'active'){
							$last_revision->closed_date = NULL;
							$last_revision->revoked_date = NULL;
						}
						
						$record->addRevision($last_revision->toArray(), $ds, false, false, false, false, false);
						$school->status = $value->affectedRecord->{$conflict->column};
            			$school->save();

					}
					else {
						$record->addRevision($last_revision->toArray(), $ds, false, false, false, false, false);
					}


					$last_revision->touch();
        			$school->touch();


					$value->selected = 1;
					$value->save();

					$conflict->status = 'resolved';
					$conflict->save();
				} else {
					$conflict->values()->create([
						'selected' => 1,
						'revision_id' => $last_revision->id,
						'value' => $last_revision[$conflict->column]
					]);

					$conflict->status = 'resolved';
					$conflict->save();
				}



				break;

			default:
				throw new Exception("Type not supported.");
				break;
		}

		return response()->json(['success'], 201);
	}




	public function getAllRepeatedSchools()
	{

		$repeated_schools = DB::select("SELECT DISTINCT name FROM school_revisions R
											 	WHERE EXISTS (
											    SELECT 1 
											    FROM school_revisions 
											    WHERE name = R.name AND number <> R.number
												);
										");

		// return $repeatedly_schools = collect($repeatedly_schools)->groupBy('name');
		return response()->json(['repeated_schools' => $repeated_schools], 200);
	}



	public function getOneRepeatedSchool($school_name)
	{
		$schools =  SchoolRevision::where('name', $school_name)
			->orderByRaw("FIELD(status , 'closed', 'active', 'revoked')")
			->get()
			->groupBy('number')
			->map(function ($deal) {
				return $deal->take(1);
			});


		foreach ($schools as $school) {
			$repeated_schools[] = $school[0];
		}

		return response()->json(['repeated_schools' => $repeated_schools], 200);
	}


	public function conflictor()
	{
		$conflicts = [];
		foreach (School::with('revisions')->limit(10000000)->cursor() as $school) {
			$conflictor = new ConflictFinder();
			$conflictor->setRecords($school->latestRevisions->toArray());
			$conflictor->setSchoolId($school->id);
			$result = $conflictor->run(true);
			if ($result)
				array_push($conflicts, $result);
		}

		return response()->json(['data' => $conflicts], 200);
	}


	public function conflicts(Request $request)
	{
		$query = DataChange::with(['values', 'affectedRecords'])->limit(100000);

		if ($column = $request->column)
			$query->where('column', $column);

		$response = $query->cursor()
			->map(function ($change) {

				$change['schools'] = $change->affectedRecords->reduce(function ($a, $b) {
					$school = ['number' => $b->number, 'id' => $b->school_id, 'name' => $b->name];
					if (!array_key_exists($b->school_id, $a))
						$a[$b->school_id] = $school;
					return $a;
				}, []);

				$change['schools'] = array_values($change['schools']);

				return $change;
			});

		return response()->json(['data' => array_values($response->toArray())], 200);
	}




	public function conflictsGrouped(Request $request)
	{
		$query = DataChange::with(['values', 'affectedRecords']);

		if ($column = $request->column)
			$query->where('column', $column);

		$response = $query->cursor()
			->map(function ($change) {

				$change['schools'] = $change->affectedRecords->reduce(function ($a, $b) {
					$school = ['number' => $b->number, 'id' => $b->school_id, 'name' => $b->name];
					if (!array_key_exists($b->school_id, $a))
						$a[$b->school_id] = $school;
					return $a;
				}, []);

				$change['schools'] = array_values($change['schools']);

				return $change;
			})->groupBy(function ($item, $key) {
				if ($item['type'] == 'data_changed')
					return implode('_', [$item['type'], $item['schools'][0]['id']]);
			})->map(function ($items, $key) {
				return [
					'schools' => $items[0]['schools'],
					'type' => $items[0]['type'],
					'items' => $items
				];
			});

		return response()->json(['data' => array_values($response->toArray())], 200);
	}


	public function getConflictSchools($conflict_id)
	{
		$conflict = DataChange::findOrFail($conflict_id);
		$schools = $conflict->getSchools();

		return response()->json(['data' => $schools], 200);
	}



	public function changeData(Request $request){

		$old_data = DataChangeValue::find($request->old_data);

		$old_data->update([
						'selected' => false,
						'type' => 'old_data'
					]);

		$current_data = DataChangeValue::find($request->current_data);
		$current_data->update([
						'selected' => true,
						'type' => 'conflict'
					]);

		$data_change = $current_data->dataChange;	
		$data_change->update(['status' => 'resolved_declare']);

		$school = $data_change->school;
		$school->update([
			'changed_data' => true,
			'old_name' => $old_data->value
		]);

		$last_revision = $school->lastRevision()->first();
		$last_revision->update([
			$data_change->column => $current_data->value,
			'updated_at' => Carbon::now()->toDateTimeString()
		]);

		$ds = DataSource::where('name', 'conflict_fixed')->first();
		$record = App::make(SchoolRecord::class);
		$record->fetchSchool($school->id);

		$last_revision['created_at'] = Carbon::now()->toDateTimeString();
		$record->addRevision($last_revision->toArray(), $ds, false, true, false);
						
		return response()->json(['success'], 201);
	}




	//TEMP
	public function dataChangesUpdate(){

		foreach(DataChange::all() as $dataChange){
			$dataChange->school_id = $dataChange->getSchools()[0]['id'];
			$dataChange->save();
		}
		return 'done!';
	}





	public function getChangedData($school_id, $column = null){

		$data_changes = DataChange::where('school_id', $school_id)->where('status', 'resolved_declare');
		($column) ? $data_changes->where('column', $column) : '';
		$data_changes = $data_changes->get();

		if(!count($data_changes)) return response()->json(['no changes available'], 204);

		else{

			$arr = [];
			foreach($data_changes as $data_change){
				foreach($data_change->values as $value){
					if($value->selected == false && $value->type == 'old_data') $arr[$data_change->column] [] = $value->value;
				}
			}

			return response()->json([$arr], 200);

		}
	}



	public function getUnresolvedSchoolConflictByColumn($school_id, $column, $ignored_value = null)
	{
		$different_values = [];
		$change = DataChange::with(['values'])->where('school_id', $school_id)->where('column', $column)->where('status', 'not_resolved')->first();
		if(isset($change->values)){
			foreach($change->values as $value){
				if($ignored_value && $ignored_value == $value->value) continue;
				$different_values[] = $value->value;
			}
		}

		return response()->json(['different_values' => array_unique($different_values)], 200);
	}




}

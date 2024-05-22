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
use Illuminate\Support\Arr;


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
		// $date = $request->date ? Carbon::parse($request->date) : Carbon::parse('1990-04-14');
		$date = Carbon::parse('2024-05-18 15:18:11');

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
			->skip(0)
			->take(200)
			// ->limit(200)
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
						'type' => 'latest_data'
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

	public function FixNameConflict(Request $request){

		$resolutions = json_decode($request->resolutions);

		$data_change = DataChange::find($request->change_id);
		$school = $data_change->school;

		foreach ($resolutions as $value_id => $res_type) {

			$change = DataChangeValue::find($value_id);
			$change->update([
						'selected' => ($res_type == 'latest_data') ? true : false,
						'type' => $res_type
			]);
		}


		if (in_array('old_data', (array) $resolutions)){
			$data_change->update(['status' => 'resolved_declare']);
			$school->update([
				'changed_data' => true,
				'old_name' => DataChangeValue::find(array_search('old_data', (array) $resolutions))->value,
			]);

		}
		else{
			$data_change->update(['status' => 'resolved']);
			$school->update([
				'changed_data' => false,
				'old_name' => NULL,
			]);

		}


		$last_revision = $school->lastRevision()->first();
		$last_revision->update([
			'name' => DataChangeValue::find(array_search('latest_data', (array) $resolutions))->value,
			'updated_at' => Carbon::now()->toDateTimeString()
		]);

		$ds = DataSource::where('name', 'conflict_fixed')->first();
		$record = App::make(SchoolRecord::class);
		$record->fetchSchool($school->id);

		$last_revision['created_at'] = Carbon::now()->toDateTimeString();
		$record->addRevision($last_revision->toArray(), $ds, false, false, false);
						
		return response()->json(['success'], 201);
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




	//To list missing data in the Private school contact information data file
	//To list schools with empty values in specific columns
	public function missingData()
	{


		$multi_statuses_schools_count = count(DataChange::where('column', 'status')->where('status', 'not_resolved')->latest()->get()->unique('school_id'));

		$active_ministry_ds = DataSource::where('name', 'active_schools')->first();

	 	$schools_with_sec_level_and_missing_ossd = 0;
        $schools_with_ossd_and_missing_principal_name = 0;
        $schools_with_ossd_and_missing_website = 0;
        $schools_with_missing_program_type = 0;

		$active_ministry_revisions = SchoolRevision::where('data_source_id', $active_ministry_ds->id)
													->where('updated_at','>=',date('Y-m-d',strtotime($active_ministry_ds->last_sync)))
													->latest()->get()->unique('school_id');


		foreach ($active_ministry_revisions as $rev) {
			if($rev->level && $rev->level != 'Elementary' && is_null($rev->ossd_credits_offered)) $schools_with_sec_level_and_missing_ossd++;
			if($rev->ossd_credits_offered && is_null($rev->principal_name)) $schools_with_ossd_and_missing_principal_name++;
			if($rev->ossd_credits_offered && is_null($rev->website)) $schools_with_ossd_and_missing_website++;
			if(is_null($rev->program_type)) $schools_with_missing_program_type++;

		}


		return response()->json([
			'sec_level_and_missing_ossd_count' => $schools_with_sec_level_and_missing_ossd,
			'ossd_and_missing_principal_name_count' => $schools_with_ossd_and_missing_principal_name,
			'ossd_and_missing_website_count' => $schools_with_ossd_and_missing_website,
			'missing_program_type_count' => $schools_with_missing_program_type,
			'ministry_datafile_url' => $active_ministry_ds->configuration['webpage'],
			'multi_statuses_schools_count' => $multi_statuses_schools_count,
		], 200);

	}



	//To get schools with empty values in specific columns in single page
	public function missingDataResults($missing_column, $filling_column = null)
	{
		
		$ministry_datafile = DataSource::where('name', 'active_schools')->first();
        $last_sync_date = date('Y-m-d',strtotime($ministry_datafile->last_sync));

        $ministry_revisions = SchoolRevision::where('data_source_id', $ministry_datafile->id)->where('updated_at','>=',$last_sync_date)->whereNull($missing_column);

        if($filling_column == 'sec_level') $ministry_revisions = $ministry_revisions->whereNotNull('level')->where('level','!=','Elementary');
        else $ministry_revisions = $ministry_revisions->whereNotNull($filling_column);


        $ministry_revisions = $ministry_revisions->latest()->get()->unique('school_id');

        return response()->json(['schools' => $ministry_revisions], 200);
	}



	//To show difference in counts between schools in SC and Ministry datafile 
	public function missingDataSourceSchools($school_status, $data_source)
	{
		$data_source_name = $school_status."_schools";
		$data_source = DataSource::where('name', $data_source_name)->first();

		$data_source_revisions = SchoolRevision::select('school_id')->where('data_source_id', $data_source->id)
													->where('updated_at','>=',date('Y-m-d',strtotime($data_source->last_sync)))
													->latest()->get()->unique('school_id');


		$data_source_schools_ids = $data_source_revisions->pluck('school_id');



		if($school_status == 'closed') $missing_schools = School::where('status', $school_status)->whereNotIn('id',$data_source_schools_ids)
										->whereHas('lastRevision', function ($q) {
		    								$q->whereDate('closed_date', '>=', Carbon::parse('01-01-2018'));
										})->get();


		elseif($school_status == 'revoked') $missing_schools = School::where('status', $school_status)->whereNotIn('id',$data_source_schools_ids)
										->whereHas('lastRevision', function ($q) {
		    								$q->whereDate('revoked_date', '>=', Carbon::parse('01-01-2015'));
										})->get();


		else $missing_schools = School::where('status', $school_status)->whereNotIn('id',$data_source_schools_ids)->get();


        return response()->json(['schools' => $missing_schools], 200);

	}



	//to list schools are listed as active while also listed as closed or revoked.
	public function multiStatusesSchools()
	{
		$multi_statuses_school = DataChange::with('school')->where('column', 'status')->where('status', 'not_resolved')->latest()->get()->unique('school_id')->pluck('school');
        return response()->json(['schools' => $multi_statuses_school], 200);
	}


	public function getSchoolSources($school_id)
	{

		$target_sources = [
			'active_schools' => 'Ministry Website',
			'revoked_schools' => 'Ministry Website',
			'closed_schools' => 'Ministry Website',
			'onsis_all_schools' => 'File Upload',
		];

		$target_sources_ids = DataSource::whereIn('name', array_keys($target_sources))->pluck('id');
		
		$revisions = SchoolRevision::where('school_id', $school_id)->whereIn('data_source_id', $target_sources_ids)->latest()->get()->unique('data_source_id');

		$school_sources = [];

		foreach ($revisions as $rev) {
			if($rev->updated_at >= date('Y-m-d',strtotime($rev->dataSource->last_sync)) ) $school_sources[] = $target_sources[$rev->dataSource->name];
		}
        return response()->json(array_values(array_unique($school_sources)), 200);

	}



	public function getAffiliations(School $school)
	{

		$lr =  $school->lastRevision;
		$fields = [];
		$search_fields = ['owner_business','corporation_contact_name','website','principal_name','telephone'];

		$revisions = SchoolRevision::with(['school.lastRevision' => function ($query) {
        	    $query->select('id','owner_business','corporation_contact_name','website','principal_name','telephone','name','status');
    		}])->select('school_id','owner_business','corporation_contact_name','website','principal_name','telephone');


		$revisions->where(function ($query) use ($search_fields, $lr, &$fields) {
		    foreach ($search_fields as $field) {
		        if ($lr->$field !== null && $lr->$field !== '') {
		            $query->orWhere($field, $lr->$field);
                    // $query->orWhere($field, 'like', '%' . $lr->$field . '%');

		            $fields[] = $field;
		        }
		    }
		});

        if ($fields) {
        	$revisions = $revisions->where('data_source_id', 5)->where('school_id', '!=', $school->id)->distinct()->get();

        	if($revisions){
	        	$results = [];
				foreach ($revisions as $revision) {

				    $filteredRevision = array_filter($revision->toArray(), function ($value, $key) use ($lr) {
				        // return $value !== null;
				        if ($key === 'school_id' || $key === 'school') {
				            return true; // Keep the school_id and school relation key
				        }
				        return ($value !== null && $value === $lr->$key);
				    }, ARRAY_FILTER_USE_BOTH);

				     // Extract and merge the last_revision data
				    if (isset($filteredRevision['school'])) {
				        $lastRevision = $filteredRevision['school']['last_revision'];
				        unset($filteredRevision['school']);
				        $filteredRevision['last_revision'] = $lastRevision;
				    }

				    $results[] = $filteredRevision;
				}

				$results = collect($results)->groupBy('school_id')->values();
				$results = $results->map(function ($group) {
				    return array_merge(...$group->all());
				});


				$affiliations = [];
				foreach ($results as $result) {
					$affiliation['school_id'] = $result['school_id'];
					$affiliation['school_status'] = $result['last_revision']['status'];
					$affiliation['school_name'] = $result['last_revision']['name'];
					$affiliation['fields'] = [];
					$affiliation['old_fields'] = [];
					$keys_names = [
						'owner_business' => 'owner',
		            	'corporation_name' => 'corporation name',
		            	'corporation_contact_name' => 'corporation',
		            	'website' => 'website',
		            	'telephone' => 'telephone',
        				'principal_name' => 'principal',
					];

					foreach ($result as $key => $value) {
						if($key == 'school_id' || $key == 'last_revision') continue;

						if(array_key_exists($key, $result['last_revision']) && $value == $result['last_revision'][$key]) $affiliation['fields'][] = $keys_names[$key];

						else{
							$date = SchoolRevision::where($key, $value)->where('school_id', $result['school_id'])->latest()->first()->created_at->toDateTimeString();
							$affiliation['old_fields'][$keys_names[$key]] = $date;
						}
					}

					$affiliations[] = $affiliation;
				}

				$affiliations;
			}

		}

		else{
			$affiliations = collect();
		}

		return response()->json(['data' => $affiliations], 200);
		
	}



}




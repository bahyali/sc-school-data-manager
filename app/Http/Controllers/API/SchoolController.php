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
		$date = $request->date ? Carbon::parse($request->date) : Carbon::parse('1990-04-14');

		$mixer_source = DataSource::where('name', 'schoolcred_engine')
			->first();

		// TODO move this inside the model
		return count(School::with(['revisions' => function ($query) use ($mixer_source, $date) {
			$query
				->where('data_source_id', $mixer_source->id)
				->where('updated_at', '>=', $date)
				->latest();
			// ->take(1);
		}])
			->where('updated_at', '>=', $date)
			// ->skip(5)
			// ->take(5)
			->get());
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




	public function multiStatusesSchools()
	{
		$multi_statuses_school = DataChange::with('school')->where('column', 'status')->where('status', 'not_resolved')->latest()->get()->unique('school_id')->pluck('school');
        return response()->json(['schools' => $multi_statuses_school], 200);
	}


	public function tempp($user_admin = false)
	{


		DB::statement('SET GLOBAL group_concat_max_len = 1000000');
		$all_schools = School::select('status', DB::raw('GROUP_CONCAT(id) as ids'))
						    ->groupBy('status')
						    ->get();

		    $all_schools->map(function($column) {
			    $column->ids = explode(',', $column->ids);
			});

		$all_active_ids = $all_schools[array_search('active', array_column($all_schools->toArray(), 'status'))]->ids;
		$all_closed_ids = $all_schools[array_search('closed', array_column($all_schools->toArray(), 'status'))]->ids;
		$all_revoked_ids = $all_schools[array_search('revoked', array_column($all_schools->toArray(), 'status'))]->ids;

		$data_sources = DataSource::whereIn('name',['active_schools','revoked_schools','closed_schools'])->groupBy('name')->select('id','name','last_sync','configuration')->get();

		$closed_ministry_ds = $data_sources[array_search('closed_schools', array_column($data_sources->toArray(),'name'))];
		$revoked_ministry_ds = $data_sources[array_search('revoked_schools', array_column($data_sources->toArray(),'name'))];
		$active_ministry_ds = $data_sources[array_search('active_schools', array_column($data_sources->toArray(),'name'))];

	
	 	$schools_with_sec_level_and_missing_ossd = 0;
        $schools_with_ossd_and_missing_principal_name = 0;
        $schools_with_ossd_and_missing_website = 0;
        $schools_with_missing_program_type = 0;
        $closed = [];
        $revoked = [];
        $active = [];
        

		$revisions = SchoolRevision::orderBy('school_id')->whereIn('data_source_id',[1,3,4])->latest()->get();
		
		foreach ($revisions as $rev) {
			if($rev->data_source_id == $active_ministry_ds->id && $rev->updated_at >= date('Y-m-d',strtotime($active_ministry_ds->last_sync))){
				if($rev->level && $rev->level != 'Elementary' && is_null($rev->ossd_credits_offered)) $schools_with_sec_level_and_missing_ossd++;
				if($rev->ossd_credits_offered && is_null($rev->principal_name)) $schools_with_ossd_and_missing_principal_name++;
				if($rev->ossd_credits_offered && is_null($rev->website)) $schools_with_ossd_and_missing_website++;
				if(is_null($rev->program_type)) $schools_with_missing_program_type++;
				$active[] = $rev->school_id;

			}
			if($rev->data_source_id == $closed_ministry_ds->id && $rev->updated_at >= date('Y-m-d',strtotime($closed_ministry_ds->last_sync)) && in_array($rev->school_id, $all_closed_ids)){
				$closed[] = $rev->school_id;
			}


			if($rev->data_source_id == $revoked_ministry_ds->id && $rev->updated_at >= date('Y-m-d',strtotime($revoked_ministry_ds->last_sync)) && in_array($rev->school_id, $all_revoked_ids)){
				$revoked[] = $rev->school_id;
			}

		}



		return response()->json([
			'sec_level_and_missing_ossd_count' => $schools_with_sec_level_and_missing_ossd,
			'ossd_and_missing_principal_name_count' => $schools_with_ossd_and_missing_principal_name,
			'ossd_and_missing_website_count' => $schools_with_ossd_and_missing_website,
			'missing_program_type_count' => $schools_with_missing_program_type,
			'ministry_datafile_url' => $active_ministry_ds->configuration['webpage'],
			'active_in_sc_but_not_in_ministry_count' => ($user_admin) ? count($all_active_ids) - count(array_unique($active)) : 0,
			'closed_in_sc_but_not_in_ministry_count' => ($user_admin) ? count($all_closed_ids) - count(array_unique($closed)) : 0,
			'revoked_in_sc_but_not_in_ministry_count' => ($user_admin) ? count($all_revoked_ids) - count(array_unique($revoked)) : 0,
		], 200);

	}



	public function getSchoolSources($school_id)
	{
		// $target_data_sources = ['onsis_all_schools', 'active_schools', 'revoked_schools', 'closed_schools'];


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




	public function MissingOpenDates(){
		// $dates = [
		// 	883708	=> '2022/09/05',
		// 	884144	=> '2022/09/06',
		// 	885709	=> '2022/08/29',
		// 	888147	=> '2022/09/06',
		// 	668386	=> '2022/08/19',
		// 	665019	=> '2022/12/16',
		// 	883821	=> '2022/09/12',
		// 	886998	=> '2022/09/01',
		// 	668441	=> '2022/09/06',
		// 	885668	=> '2022/09/01',
		// 	882898	=> '2022/09/01',
		// 	665820	=> '2022/09/01',
		// 	667233	=> '2022/10/31',
		// 	669198	=> '2022/09/01',
		// 	667979	=> '2022/07/04',
		// 	668843	=> '2022/09/06',
		// 	882117	=> '2022/08/18',
		// 	666535	=> '2022/09/06',
		// 	886462	=> '2022/08/31',
		// 	665334	=> '2022/09/19',
		// 	886138	=> '2022/09/01',
		// 	668462	=> '2022/08/01',
		// 	889485	=> '2022/09/01',
		// 	669156	=> '2022/08/30',
		// 	668824	=> '2022/09/06',
		// 	665867	=> '2022/07/04',
		// 	665974	=> '2022/09/13',
		// 	667231	=> '2022/09/12',
		// 	669243	=> '2022/09/06',
		// 	668704	=> '2022/07/14',
		// 	886167	=> '2022/09/07',
		// 	666756	=> '2022/09/13',
		// 	881955	=> '2022/09/06',
		// 	884866	=> '2022/09/01',
		// 	881754	=> '2022/09/05',
		// 	884605	=> '2022/09/06',
		// 	665122	=> '2022/09/06',
		// 	667874	=> '2022/09/01',
		// 	884180	=> '2022/09/12',
		// 	884832	=> '2022/09/06',
		// 	885075	=> '2022/09/07',
		// 	665824	=> '2022/07/14',
		// 	669360	=> '2022/09/06',
		// 	881931	=> '2022/09/06',
		// 	882938	=> '2022/09/01',
		// 	669501	=> '2022/07/02',
		// 	668371	=> '2022/07/11',
		// 	882627	=> '2022/08/31',
		// 	666260	=> '2022/09/19',
		// 	669314	=> '2022/09/07',
		// 	666457	=> '2022/09/07',
		// 	881468	=> '2022/09/12',
		// 	885187	=> '2022/09/01',
		// 	886070	=> '2022/08/01',
		// 	665532	=> '2022/09/06',
		// 	884355	=> '2022/09/05',
		// 	881705	=> '2022/09/06',
		// 	885074	=> '2022/07/04',
		// 	669859	=> '2022/07/04',
		// 	887694	=> '2022/09/06',
		// 	665593	=> '2022/08/30',
		// 	665194	=> '2022/07/22',
		// 	669373	=> '2022/08/31',
		// 	883283	=> '2022/09/01',
		// 	883894	=> '2022/10/31',
		// 	666642	=> '2022/09/06',
		// 	881941	=> '2022/09/01',
		// 	665638	=> '2022/09/06',
		// 	668713	=> '2022/09/01',
		// 	889728	=> '2022/08/31',
		// 	668835	=> '2022/09/06',
		// 	883703	=> '2022/08/17',
		// 	888873	=> '2022/09/15',
		// 	665007	=> '2022/09/16',
		// 	668633	=> '2022/09/01',
		// 	885455	=> '2022/08/24',
		// 	666506	=> '2023/01/03',
		// 	665957	=> '2022/09/01',
		// ];



		$dates = [
			884609	=> '2023/05/01',
			668440	=> '2023/05/01',
			668168	=> '2023/05/01',
			669812	=> '2023/03/01',
			884329	=> '2023/03/01', //not exist
			882645	=> '2023/03/01',
			669907	=> '2022/10/01',
			669502	=> '2023/01/01',
			882342	=> '2023/01/01',
			881377	=> '2023/03/01',
			669767	=> '2023/03/01',
			887502	=> '2023/03/01',
			886876	=> '2023/04/01',
			884747	=> '2023/05/01',
			669670	=> '2023/03/01',
		];



		$schools = [];
		foreach ($dates as $key => $value) {
			$school = School::where('number', $key)->first();
			$school->lastRevision->open_date = $value;

			$school->touch();
			$school->lastRevision->touch();

		}


		return 'Done!';

	}





}




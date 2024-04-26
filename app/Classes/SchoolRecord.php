<?php

namespace App\Classes;

use App\Models\School;
use App\Models\DataSource;
use App\Models\SchoolRevision;
use App\Models\DataChange;
use App\Models\Log;


use Exception;

class SchoolRecord implements ISchoolRecord
{
    protected $model;

    protected $school;

    public function __construct(School $school, $id = 0)
    {
        $this->model = $school;

        if ($id)
            $this->fetchSchool($id);
    }

    public function addSchool($school_number)
    {
        $this->school = $this->model->updateOrCreate(['number' => $school_number]);

        return $this;
    }

    public function addRevision($revision, $data_source, $remix = false, $associate = false, $check_conflict = false, $check_status = false, $fill_dates = false)
    {
        if (!$this->school)
            throw new Exception("We need a school to create a revision!");


        if($revision['diploma_2022_2023']){
            $rev = SchoolRevision::find($this->school->revision_id);
            $rev->diploma_2022_2023 = $revision['diploma_2022_2023'];
            $rev->save();
            $this->school->touch();
        }

        return $this;

    }

    public function fetchSchool($id)
    {
        $this->school = $this->model->findOrFail($id);

        return $this;
    }

    public function getSchool()
    {
        return $this->school;
    }

    public function setSchool(School $school)
    {
        $this->school = $school;
    }

    public function remix()
    {
        $mixer = DataMixer::getInstance();
        $mixer->run($this);
    }


    public function checkConflict($new_rev, $last_rev)
    {

        // $search_columns = ['name', 'principal_name','address_line_1'];
        $search_columns = ['name','address_line_1'];
        $revs = SchoolRevision::select($search_columns)->whereIn('id', [$new_rev->id, $last_rev->id])->get();
        $conflicts = [];
        foreach ($revs as $rev) {
            foreach ($search_columns as $search_column) {
                if($rev[$search_column]){
                    $conflicts[$search_column] [] = trim($rev->$search_column);
                    $conflicts[$search_column]  = array_unique(array_values($conflicts[$search_column]));
                }
            }
        }

        foreach ($conflicts as $key => $value) {
            if(count($value) == 1){unset($conflicts[$key]);}
        }
        if($conflicts) $this->school->conflict = true;
        else $this->school->conflict = false;   
        
        $this->school->save();
        
    }


    public function checkStatus($incoming_status){
        if( $this->school->status == 'revoked' && $incoming_status == 'closed') return 'revoked, closed';
        if( $this->school->status == 'closed' && $incoming_status == 'revoked') return 'revoked, closed';
        if( $this->school->status == 'revoked, closed' && $incoming_status == 'closed') return 'revoked, closed';
        if( $this->school->status == 'revoked, closed' && $incoming_status == 'revoked') return 'revoked, closed';
        
        return $incoming_status;

    }


    public function checkStatusForMixingRevision($incoming_status){

        $last_revision_updated_at = $this->school->lastRevision->updated_at->toDateTimeString();
        $ignored_data_sources = DataSource::whereNotIn('resource', ['auto_mixer', 'conflict_fixed'])->pluck('id');

        $revisions = SchoolRevision::select('id','status')->where('school_id', $this->school->id)->whereIn('data_source_id', $ignored_data_sources)->where('updated_at', '>=', $last_revision_updated_at)->where('status','!=', NULL)->orderBy('id', 'DESC')->get();


        $statuses = array_column($revisions->toArray(), 'status');



        if(count(array_unique($statuses)) === 1) return $incoming_status; //this means all statuses are the same


        if(!in_array('active', $statuses)) return 'revoked, closed'; //this means statuses exists only in closed and revoked


        else {

            $active_key = array_search('active', array_column($revisions->toArray(), 'status'));
            $not_active_key = !array_search('active', array_column($revisions->toArray(), 'status'));

             $hash = md5(serialize([
                'type' => 'change',
                'column' => 'status',
                'values' => ['active', $revisions[$not_active_key]->status],
                'school_id' => $this->school->id
            ]));

            $dataChange = DataChange::updateOrCreate([
                'type' => 'change',
                'column' => 'status',
                'hash' => $hash,
                'school_id' => $this->school->id
                ],
                [
                'hash' => $hash
                ]
            );


            $dataChange->values()->updateOrCreate([
                'data_change_id' => $dataChange->id,
                'revision_id' => $revisions[$active_key]->id,
                'value' => 'active'
            ]);


            $dataChange->values()->updateOrCreate([
                'data_change_id' => $dataChange->id,
                'revision_id' => $revisions[$not_active_key]->id,
                'value' => $revisions[$not_active_key]->status
            ]);

            return $incoming_status;

        } 
        
    }



    public function fillDates($revision_model)
    {

        $revoked_data_row = SchoolRevision::where('school_id', $this->school->id)->where('revoked_date', '!=', NULL)->latest()->first();
        $closed_date_row = SchoolRevision::where('school_id', $this->school->id)->where('closed_date', '!=', NULL)->latest()->first();
        $last_revision = $this->school->lastRevision()->first();


        if($revoked_data_row && $revision_model->revoked_date == NULL) {
            $revision_model->revoked_date = $revoked_data_row->revoked_date;
            $last_revision->revoked_date = $revoked_data_row->revoked_date;
        }
        if($closed_date_row && $revision_model->closed_date == NULL ) {
            $revision_model->closed_date = $closed_date_row->closed_date;
            $last_revision->closed_date = $closed_date_row->closed_date;
        }

        $revision_model->save();
        $revision_model->touch();
        $last_revision->save();
        $last_revision->touch();

    }



    public function updateOntarioLogs($data_source, $hash, $revision)
    {
        if($this->school->lastRevision()->first()){
            $existed = $this->school->revisions()->where('hash', $hash)->first();
            if ($existed) $revision_model = $existed; //do nothing because it is existed before and there is no effect
            else {
                $revision_model = $this->school->revisions()->firstOrCreate(['hash' => $hash], $revision);
                //CREATE NEW LOG WITH EFFECT equal 'CHANGE' because there are already old revisions
                Log::create([
                    'revision_id' => $revision_model->id,
                    'effect' => 'change',
                    'resource' => $data_source->configuration['file_name'],
                    'school_id' => $this->school->id
                ]);
            }
        }

        else{
            $revision_model = $this->school->revisions()->firstOrCreate(['hash' => $hash], $revision);
            //CREATE NEW LOG WITH EFFECT = 'ADDED'
            Log::create([
                'revision_id' => $revision_model->id,
                'effect' => 'added',
                'resource' => $data_source->configuration['file_name'],
                'school_id' => $this->school->id
            ]);
        }

        return $revision_model;
    }
}

interface ISchoolRecord
{
    function fetchSchool($id);

    function addRevision($revision, DataSource $data_source, $remix = true);

    function addSchool($school_number);
}

<?php

namespace App\Classes;

use App\Models\School;
use App\Models\DataSource;
use App\Models\SchoolRevision;


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

    public function addRevision($revision, $data_source, $remix = true, $associate = false, $check_conflict = true)
    {
        if (!$this->school)
            throw new Exception("We need a school to create a revision!");

        $revision['data_source_id'] = $data_source->id;

        //to check if school can have Revoked + Closed statuses at the same time
        if( isset($revision['status'])) $revision['status'] = $this->checkStatus($revision['status']);

        
        // Sort array to standardize fingerprint
        ksort($revision);

        $hash = md5(serialize($revision));
        $revision_model = $this->school->revisions()->firstOrCreate(['hash' => $hash], $revision);

        if ($associate) {
            $this->school->lastRevision()->associate($revision_model);
            $this->school->status = $revision['status'];
            $this->school->save();
        }


        if ($check_conflict && $this->school->lastRevision()->first())
            $this->checkConflict($revision_model, $this->school->lastRevision()->first());
        

        if ($remix)
            $this->remix();

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

        $search_columns = ['name', 'principal_name','address_line_1'];
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
        

        dd($conflicts);
        $this->school->save();
        
    }


    public function checkStatus($incoming_status){
        if( $this->school->status == 'revoked' && $incoming_status == 'closed') return 'revoked, closed';
        if( $this->school->status == 'closed' && $incoming_status == 'revoked') return 'revoked, closed';
        if( $this->school->status == 'revoked, closed' && $incoming_status == 'closed') return 'revoked, closed';
        if( $this->school->status == 'revoked, closed' && $incoming_status == 'revoked') return 'revoked, closed';
        
        return $incoming_status;

    }
}

interface ISchoolRecord
{
    function fetchSchool($id);

    function addRevision($revision, DataSource $data_source, $remix = true);

    function addSchool($school_number);
}

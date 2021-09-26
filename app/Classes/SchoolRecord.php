<?php

namespace App\Classes;

use App\Models\School;
use App\Models\DataSource;

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

    public function addRevision($revision, $data_source, $remix = true, $associate = false)
    {
        if (!$this->school)
            throw new Exception("We need a school to create a revision!");

        $revision['data_source_id'] = $data_source->id;
        
        // Sort array to standardize fingerprint
        ksort($revision);

        $hash = md5(serialize($revision));
        $revision_model = $this->school->revisions()->firstOrCreate(['hash' => $hash], $revision);

        if ($associate) {
            $this->school->lastRevision()->associate($revision_model);
            $this->school->save();
        }

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
}

interface ISchoolRecord
{
    function fetchSchool($id);

    function addRevision($revision, DataSource $data_source, $remix = true);

    function addSchool($school_number);
}

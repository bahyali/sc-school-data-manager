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
            $this->getSchool($id);
    }

    public function addSchool($school_number)
    {
        $this->school = $this->model->updateOrCreate(['number' => $school_number]);
        
        return $this;
    }

    public function addRevision($revision, $data_source)
    {
        if (!$this->school)
            throw new Exception("We need a school to create a revision!");

        $revision['data_source_id'] = $data_source->id;

        $revision_model = $this->school->revisions()->firstOrCreate($revision);
        $this->school->lastRevision()->associate($revision_model);
        $this->school->save();

        return $this;
    }

    public function getSchool($id)
    {
        $this->school = $this->model->findOrFail($id);

        return $this;
    }
}

interface ISchoolRecord
{
    function getSchool($id);

    function addRevision($revision, DataSource $data_source);

    function addSchool($school_number);
}

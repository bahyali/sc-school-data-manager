<?php

namespace App\Classes;

use App\Models\School;
use App\Models\DataSource;
use App\Models\SchoolRevision;
use App\Models\Log;

use Exception;

class OntarioSchoolRecord
{
    protected $model;

    protected $school;

    public function __construct(School $school, $id = 0)
    {

        $this->model = $school;

        if ($id)
            $this->fetchSchool($id);
    }



    public function fetchSchool($id)
    {
        $this->school = $this->model->findOrFail($id);
        return $this;
    }


    public function addRevision($revision, $data_source)
    {
        if (!$this->school)
            throw new Exception("We need a school to create a revision!");


        $revisionWithoutTimestamps = $revision;
        unset($revisionWithoutTimestamps['created_at']);
        unset($revisionWithoutTimestamps['updated_at']);

        $revision['data_source_id'] = $data_source->id;


        ksort($revisionWithoutTimestamps);

        $hash = md5(serialize($revisionWithoutTimestamps));

        $revision_model = $this->updateOntarioLogs($data_source, $hash, $revision);

        return $this;
    }



    public function updateOntarioLogs($data_source, $hash, $revision)
    {
        if($this->school->created_at > $revision['created_at'])
        {
            $originalUpdatedAt = $this->school->updated_at;
            $this->school->created_at = $revision['created_at'];
            $this->school->timestamps = false;
            $this->school->save();

            $this->school->updated_at = $originalUpdatedAt;
            $this->school->timestamps = true;
            $this->school->save();


            $revision_model = $this->school->revisions()->firstOrCreate(['hash' => $hash], $revision);
            // Log::create([
            //     'revision_id' => $revision_model->id,
            //     'effect' => 'added',
            //     'resource' => $data_source->configuration['file_name'],
            //     'school_id' => $this->school->id
            // ]);

            Log::updateOrCreate([
                'school_id' => $this->school->id,
                'effect' => 'added'
            ],
            [
                'revision_id' => $revision_model->id,
                'resource' => $data_source->configuration['file_name']
            ]);




        }
        if($this->school->lastRevision()->first()){
            $existed = $this->school->revisions()->where('hash', $hash)->first();
            if ($existed) $revision_model = $existed; 

            else {
                $revision_model = $this->school->revisions()->firstOrCreate(['hash' => $hash], $revision);

                 $revision_model->update([
                    'created_at' => $revision['created_at'],
                    'updated_at' => $revision['updated_at'],
                ]);

                Log::create([
                    'revision_id' => $revision_model->id,
                    'effect' => 'change',
                    'resource' => $data_source->configuration['file_name'],
                    'school_id' => $this->school->id
                ]);
            }
        }



        return $revision_model;
    }
}



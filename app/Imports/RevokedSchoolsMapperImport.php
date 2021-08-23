<?php

namespace App\Imports;

use App\Models\School;



class RevokedSchoolsMapperImport 
{
    private $data_source;
    private $json;

    public function __construct($data_source, $json)
    {
        $this->configuration = $data_source->configuration;
        $this->status = $data_source->name;
        $this->json = $json;
    }


    public function store()
    {
        $array = [];
        $array['status'] = $this->status;


        foreach ($this->josn as $row) {
            foreach ($this->configuration as $key => $value) {

                $array[$key] = $row[$value];
            }
        }
        return new School($array);
    }
}

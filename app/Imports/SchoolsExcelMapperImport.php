<?php

namespace App\Imports;

use App\Models\School;
use App\Models\SchoolRevision;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\WithStartRow;


class SchoolsExcelMapperImport implements ToModel, WithStartRow

{
    private $data_source;
    private $status;

    public function __construct($data_source, $status)
    {
        $this->configuration = $data_source->configuration;
        $this->status = $status;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        $array = [];
        $array['status'] = $this->status;

        foreach ($this->configuration as $key => $value) {
            $array[$key] = $row[$value];
        }
        // dd($array);
        $school = School::updateOrCreate(['number'=>$array['number']]);
        $array['school_id'] = $school->id;

        SchoolRevision::create($array);

        $latest_ver = $school->getLatestVersion();
        $school->revision_id = $latest_ver->id;
        $school->save();

    }
}

<?php

namespace App\Imports;

use App\Models\School;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;


class SchoolsExcelMapperImport implements ToModel, WithStartRow
{
    use Importable;

    private $status;

    public function __construct($data_source, $status)
    {
        $this->configuration = $data_source->configuration;
        $this->status = $status;
    }

    public function startRow(): int
    {
        // TODO make this configurable 
        return 2;
    }

    public function model(array $row)
    {
        $array = [];
        $array['status'] = $this->status;

        foreach ($this->configuration as $key => $value) {
            $array[$key] = $row[$value];
        }

        $school = School::updateOrCreate(['number' => $array['number']]);
        $revision = $school->revisions()->create($array);
        $school->lastRevision()->associate($revision);
        $school->save();

        return $school;
    }
}

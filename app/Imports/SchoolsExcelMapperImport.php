<?php

namespace App\Imports;

use App\Models\School;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\WithStartRow;


class SchoolsExcelMapperImport implements ToModel, WithStartRow

{
    private $data_source;

    public function __construct($data_source)
    {
        $this->configuration = $data_source->configuration;
        $this->status = $data_source->name;
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
        return new School($array);
    }
}

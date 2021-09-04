<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;

use Illuminate\Support\Facades\App;
use App\Classes\SchoolRecord;



class SchoolsExcelMapperImport implements ToModel, WithStartRow
{
    use Importable;

    private $status;
    private $data_source;
    private $configuration;

    public function __construct($data_source, $status)
    {
        $this->data_source = $data_source;
        $this->configuration = $data_source->configuration;
        $this->status = $status;
    }

    public function startRow(): int
    {
        // TODO make this configurable 
        return 2;
    }

    // TODO convert to MAP since we don't need models anymore.
    public function model(array $row)
    {
        $array = [];        
        // Apply column overrides
        if (count($this->configuration['overrides']) > 0)
            foreach ($this->configuration['overrides'] as $key => $value)
                $array[$key] = $value;

        // Map
        foreach ($this->configuration['mapping'] as $key => $value) {
            $array[$key] = $row[$value];
        }

        $record = App::make(SchoolRecord::class);

        $school = $record->addSchool($array['number']);
        $school->addRevision($array, $this->data_source);
    }
}

<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

use Illuminate\Support\Facades\App;
use App\Classes\SchoolRecord;


class SchoolsExcelMapperImport implements ToModel, WithStartRow
{
    use Importable;

    private $data_source;
    private $configuration;

    public function __construct($data_source)
    {
        $this->data_source = $data_source;
        $this->configuration = $data_source->configuration;
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
            $row_value = $row[$value];

            // is it a date?
            if ($row_value && in_array($key, $this->configuration['date_columns'])) {
                $array[$key] = $this->transformDate($row_value);
            } else {
                $array[$key] = $row_value;
            }
        }

        if ($array['number'] == null || $array['status'] == null)
            
            return;

            if($row[1] != 668726) return;
            
        $record = App::make(SchoolRecord::class);

        $school = $record->addSchool($array['number']);
        $school->addRevision($array, $this->data_source);
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }
}

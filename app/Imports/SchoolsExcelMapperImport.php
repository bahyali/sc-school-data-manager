<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

use Illuminate\Support\Facades\App;
use App\Classes\SchoolRecord;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class SchoolsExcelMapperImport implements ToModel, WithStartRow, WithHeadingRow

{
    use Importable;

    private $data_source;
    private $configuration;
    private $headers = [];


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


    public function headingRow(): int
    {
        return 1; // The row containing column names
    }



    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }



    // TODO convert to MAP since we don't need models anymore.
    public function model(array $row)
    {

        if (empty($this->headers)) {
            $this->setHeaders(array_keys($row)); // Store headers only once
        }

        // Now $this->headers contains the headers for the entire file

        $array = [];

        // Apply column overrides
        if (count($this->configuration['overrides']) > 0)
            foreach ($this->configuration['overrides'] as $key => $value)
                $array[$key] = $value;


        // Map
        foreach( $row as $key => $value ){
            if( !empty($key) && isset(config('app.all_columns')[$key]) ){ //To ignore empty columns
                $array_key = config('app.all_columns')[$key];
                $array_value = $value;

                if ($array_value && in_array($array_key, $this->configuration['date_columns'])) $array_value = $this->transformDate($value);

                $array[$array_key] = $array_value;
            }
        }

        if (!isset($array['number']) || $array['number'] == null || !is_numeric($array['number']) || $array['status'] == null)
            
            return;


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

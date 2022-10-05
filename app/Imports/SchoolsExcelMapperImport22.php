<?php

namespace App\Imports;
use Illuminate\Support\Collection;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

use Illuminate\Support\Facades\App;
use App\Classes\SchoolRecord;


class SchoolsExcelMapperImport22 implements WithStartRow, ToCollection
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
    public function collection(Collection $rows)
    {
        dd($rows[0]);
        $rows = $rows->sortBy('0');//to sort by BSID

        //to handle redundancy cames from multi-sheets 

        $last_row = count($rows);
        $current_row = 1;
        $previous_row = collect(0);

        foreach ($rows as $row)
        {

            //first-row
            if($previous_row[0] == 0) $previous_row = $row;

            //check if current-row still equal the previous row BSID and merge them in one row
            elseif($previous_row[0] == $row[0])
            {

                //merging
                foreach($row as $key => $value){
                    $previous_row[$key] = ($previous_row[$key]) ?  : $value; 
                }

                //check if this is the last row
                if($current_row == $last_row) $this->storeMergingRows($previous_row);
            }

            else
            {
                $this->storeMergingRows($previous_row);
                $previous_row = $row;
            }


            $current_row++;

        }

    }


    public function storeMergingRows($row){
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
                if($key == 'status') $array[$key] = $this->handleSchoolStatus($row_value);
                else $array[$key] = $row_value;
            }
        }

        if ($array['number'] == null || $array['status'] == null)
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



    private function handleSchoolStatus($status_string)
    {

        $exploded_status_string = explode(' ', strtolower($status_string));

        $statuses = ['active' => 'active', 'revoked' => 'revoked', 'closed' => 'closed', 'active' => 'open'];
        $status = array_intersect($statuses, $exploded_status_string);

        return array_key_first($status);
    }
}

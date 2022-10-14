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
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;


class SchoolsExcelMapperImportMulti implements WithStartRow, ToCollection, WithHeadingRow, SkipsEmptyRows
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

        
        $sorted_by_col = (isset($row[0]['school_number'])) ? 'school_number' : 'bsid'  ;
        
        $rows = $rows->sortBy($sorted_by_col);//to sort by BSID
        //to handle redundancy cames from multi-sheets 

        $last_row = count($rows);
        $current_row = 1;
        $previous_row = 0;

        foreach ($rows as $row)
        {


            //first-row
            if($current_row == 1) $previous_row = $row;

            //check if current-row still equal the previous row BSID and merge them in one row
            // elseif($previous_row[0] == $row[0])
            elseif( isset($row['bsid']) && $row['bsid'] == $previous_row['bsid'] || isset($row['school_number']) && $row['school_number'] == $previous_row['school_number'] ) 
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



        //Map
        foreach( $row as $key => $value ){
            if( !empty($key) && isset(config('app.all_columns')[$key]) ){ //To ignore empty columns
                $array_key = config('app.all_columns')[$key]; 
                $array_value = $value;

                if ($array_value && in_array($array_key, $this->configuration['date_columns'])) $array_value = $this->transformDate($value);
                if($array_key == 'status') $array_value = $this->handleSchoolStatus($value);

                $array[$array_key] = $array_value;
            }
        }



        // Apply column overrides
        if (count($this->configuration['overrides']) > 0)
            foreach ($this->configuration['overrides'] as $key => $value)
                $array[$key] = $value;


        if (!isset($array['number']) || $array['number'] == null || !is_numeric($array['number']))
            return;
        

        $record = App::make(SchoolRecord::class);
        $school = $record->addSchool($array['number']);

        if( isset($array['principal_name']) && isset($array['principal_last_name'])) $array['principal_name'] = $array['principal_name'].' '.$array['principal_last_name'];


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




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

use Illuminate\Support\Facades\DB;



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

        
        // $sorted_by_col = (isset($row[0]['school_number'])) ? 'school_number' : (isset($row[0]['bsid_school_number'])) ? 'bsid_school_number' : 'bsid'  ;


        $sorted_by_col = (isset($rows[0]['school_number'])) ? 'school_number' :
               ((isset($rows[0]['bsid_school_number'])) ? 'bsid_school_number' : 'bsid');



        
        // dd($rows[0]);

        //to handle redundancy came from multi-sheets 
        // $rows = $rows->sortBy($sorted_by_col);//to sort by BSID

        $last_row = count($rows);
        $current_row = 1;
        $previous_row = 0;



        DB::beginTransaction();

        foreach ($rows as $row)
        {


            //first-row
            if($current_row == 1) $previous_row = $row;

            //check if current-row still equal the previous row BSID and merge them in one row
            // elseif($previous_row[0] == $row[0])
            elseif( isset($row['bsid']) && $row['bsid'] == $previous_row['bsid'] || isset($row['school_number']) && $row['school_number'] == $previous_row['school_number'] || isset($row['bsid_school_number']) && $row['bsid_school_number'] == $previous_row['bsid_school_number'] ) 
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

        DB::commit();


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


                // if(trim($array_value) == 'Section 21, Personal Privacy') $array_value = 'adasd';
                if(stripos($array_value,"personal privacy")!== false && stripos($array_key,"email") !== false) $array_value = NULL;

                $array[$array_key] = $array_value;
            }
        }



        // Apply column overrides
        if (count($this->configuration['overrides']) > 0)
            foreach ($this->configuration['overrides'] as $key => $value)
                $array[$key] = $value;

        //to skip if there is NO bsid
        if (!isset($array['number']) || $array['number'] == null || !is_numeric($array['number']))
            return;
        

        //sometimes sheets does not contain status column directly it contains columns like(status_closed, status_open) with yes or no values
        $array['status'] = $this->finalCheckForStatus($array);


        $record = App::make(SchoolRecord::class);
        $school = $record->addSchool($array['number']);

        //sometimes ONSIS does not provide status in sheets like principal, affiliations, etc...
        if( !$array['status'] && $this->data_source->name == 'onsis_all_schools')
        {
            $array['status'] = $school->status;
        }

        //to skip if there is NO status
        if(!in_array($array['status'], ['active', 'revoked', 'closed'])) return;


        if( isset($array['principal_name']) && isset($array['principal_last_name'])) $array['principal_name'] = $array['principal_name'].' '.$array['principal_last_name'];


        $school->addRevision($array, $this->data_source);
        // dd($array);
    }

    // private function transformDate($value, $format = 'Y-m-d')
    // {
    //     try {
    //         return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
    //     } catch (\ErrorException $e) {
    //         return \Carbon\Carbon::createFromFormat($format, $value);
    //     }
    // }



    private function transformDate($value, $format = 'Y-m-d')
    {
        if (is_numeric($value)) {
            try {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            } catch (\Exception $e) {
                return null;
            }
        } else {
            try {
                return \Carbon\Carbon::createFromFormat($format, $value);
            } catch (\Exception $e) {
                return null;
            }
        }
    }




    private function handleSchoolStatus($status_string)
    {

        $exploded_status_string = explode(' ', strtolower($status_string));

        $statuses = ['active' => 'active', 'revoked' => 'revoked', 'closed' => 'closed', 'active' => 'open'];
        $status = array_intersect($statuses, $exploded_status_string);

        return array_key_first($status);
    }



    private function finalCheckForStatus($array)
    {

        if(isset($array['status'])) return $array['status'];
        if(isset($array['status_open']) && strtolower($array['status_open']) == 'yes') return 'active';
        if(isset($array['status_closed']) && strtolower($array['status_closed']) == 'yes') return 'closed';
        if(isset($array['status_revoked']) && strtolower($array['status_revoked']) == 'yes') return 'revoked';

        return null;
    }
}




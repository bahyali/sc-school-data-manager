<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use App\Models\School;
use Illuminate\Support\Facades\App;
use App\Classes\OntarioSchoolRecord;
use Carbon\Carbon;

class TempSchoolsExcelMapperImport implements ToModel, WithStartRow
{
    use Importable;

    private $data_source;
    private $configuration;
    private $mapping;
    private $file_date;

    public function __construct($data_source)
    {


        // dd($data_source);
        $this->data_source = $data_source;
        $this->configuration = $data_source->configuration;
        $this->file_date = $this->extractYearAndMonth($this->configuration['file_name']);

        $this->mapping = [
                          "name" => 0,
                          "address_line_1" => 1,
                          "address_line_2" => 2,
                          "address_line_3" => 4,
                          "telephone" => 5,
                          "fax" => 6,
                          "region" => 7,
                          "number" => 8,
                          "principal_name" => 9,
                          "level" => 10,
                          "ossd_credits_offered" => 11,
                          // "type" => 12,
                          "association_membership" => 13,
        ];


        if (array_reduce(['2016-09','2016-10','2016-11','2016-12','2017','2018'], fn($carry, $needle) => $carry || str_contains($this->file_date, $needle), false)) {
            $this->mapping = [
                          "name" => 0,
                          "suite" => 1,
                          "po_box" => 2,
                          "address_line_1" => 3,
                          "address_line_2" => 4,
                          "address_line_3" => 6,
                          "telephone" => 7,
                          "fax" => 8,
                          "region" => 9,
                          "number" => 10,
                          "principal_name" => 11,
                          "level" => 12,
                          "special_conditions_code" => 13,
                          "ossd_credits_offered" => 14,
                          // "type" => 15,
                          "association_membership" => 16,
            ];
        }



        // if(str_contains($this->file_date, ['2016-09','2016-10','2016-11','2016-12','2017','2018']))
        //     $this->mapping = [
        //                   "name" => 0,
        //                   "suite" => 1,
        //                   "po_box" => 2,
        //                   "address_line_1" => 3,
        //                   "address_line_2" => 4,
        //                   "address_line_3" => 6,
        //                   "telephone" => 7,
        //                   "fax" => 8,
        //                   "region" => 9,
        //                   "number" => 10,
        //                   "principal_name" => 11,
        //                   "level" => 12,
        //                   "special_conditions_code" => 13,
        //                   "ossd_credits_offered" => 14,
        //                   "type" => 15,
        //                   "association_membership" => 16,
        //     ];



        if(str_contains($this->file_date, '2019-'))
            $this->mapping = [
                          "name" => 0,
                          "suite" => 1,
                          "po_box" => 2,
                          "address_line_1" => 3,
                          "address_line_2" => 4,
                          "address_line_3" => 6,
                          "telephone" => 7,
                          "fax" => 8,
                          "region" => 9,
                          "number" => 10,
                          "website" => 11,
                          "level" => 12,
                          "special_conditions_code" => 13,
                          "ossd_credits_offered" => 14,
                          // "type" => 15,
                          "association_membership" => 16,
            ];


        if($this->file_date == '2019-12-01')
            $this->mapping = [
                          "name" => 0,
                          "suite" => 1,
                          "po_box" => 2,
                          "address_line_1" => 3,
                          "address_line_2" => 4,
                          "address_line_3" => 6,
                          "telephone" => 7,
                          "fax" => 8,
                          "region" => 9,
                          "number" => 10,
                          "website" => 11,
                          "principal_name" => 12,
                          "level" => 13,
                          "special_conditions_code" => 14,
                          "ossd_credits_offered" => 15,
                          // "type" => 16,
                          "association_membership" => 17,
            ];





        if (array_reduce(['2020-', '2021-', '2022-'], fn($carry, $needle) => $carry || str_contains($this->file_date, $needle), false)) {
            $this->mapping = [
                          "name" => 0,
                          "number" => 1,
                          "ossd_credits_offered" => 2,
                          "principal_name" => 3,
                          "suite" => 4,
                          "po_box" => 5,
                          "address_line_1" => 6,
                          "address_line_2" => 7,
                          "address_line_3" => 9,
                          "telephone" => 10,
                          "fax" => 11,
                          "region" => 12,
                          "website" => 13,
                          "level" => 14,
                          "special_conditions_code" => 15,
                          // "type" => 16,
                          "association_membership" => 17,
            ];
        }


            // dd($this->mapping);
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
        foreach ($this->mapping as $key => $value) {
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

        if(!$this->file_date)
            return;



        $array['updated_at'] = Carbon::parse($this->file_date)->format('Y-m-d H:i:s');
        $array['created_at'] = Carbon::parse($this->file_date)->format('Y-m-d H:i:s');

        // check first if school exist
        $school = School::where('number', $array['number'])->first();
        if(!$school)
            return;


        $record = App::make(OntarioSchoolRecord::class);

        $school = $record->fetchSchool($school->id);
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



    private function extractYearAndMonth($string) {
    // preg_match('/(\d{1,2})_([a-zA-Z]+) (\d{4})/', $string, $matches);
    preg_match('/(\d{1,2})_([a-zA-Z]+)_?(\d{4})/', $string, $matches);
    if ($matches) {
        $month = date_parse($matches[2])['month'];
        if ($month !== false && $month !== 0) {
            return sprintf('%04d-%02d-01', $matches[3], $month);
        }
    }
    return null;
}




}

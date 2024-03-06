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
                          "type" => 12,
                          "association_membership" => 13,
                          // "website" => 13,
                          // "special_conditions_code" => 15,
                        ];
    }

    public function startRow(): int
    {
        // TODO make this configurable 
        return 2;
    }


    // TODO convert to MAP since we don't need models anymore.
    public function model(array $row)
    {
        // dd($this->file_date);

        // dd($row);

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
    preg_match('/(\d{1,2})_([a-zA-Z]+) (\d{4})/', $string, $matches);
    if ($matches) {
        $month = date_parse($matches[2])['month'];
        if ($month !== false && $month !== 0) {
            return sprintf('%04d-%02d-01', $matches[3], $month);
        }
    }
    return null;
}




}

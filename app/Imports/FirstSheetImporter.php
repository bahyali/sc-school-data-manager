<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Importable;


class FirstSheetImporter implements WithMultipleSheets
{
    use Importable;

    private $data_source;

    public function __construct($data_source)
    {
       $this->data_source = $data_source;
    }

    public function sheets(): array
    {

        if($this->data_source->name == 'onsis_all_schools'){
            return [
                // new SchoolsExcelMapperImportMulti($this->data_source);
                'School Details' => new SchoolsExcelMapperImportMulti($this->data_source),
                'Affiliation' => new SchoolsExcelMapperImportMulti($this->data_source),
                'Association' => new SchoolsExcelMapperImportMulti($this->data_source),
                'Principal' => new SchoolsExcelMapperImportMulti($this->data_source),
                'Address' => new SchoolsExcelMapperImportMulti($this->data_source),
                // 'Principal' => new SchoolsPrincipalExcelMapper($this->data_source),
            ];
        }else{
            return [
                new SchoolsExcelMapperImport($this->data_source)
            ];
        }
    }




}

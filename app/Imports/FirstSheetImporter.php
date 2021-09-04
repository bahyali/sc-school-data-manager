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
        return [
            new SchoolsExcelMapperImport($this->data_source)
        ];
    }
}

<?php

namespace App\Classes;

use App\Classes\SchoolRecord;
use Illuminate\Support\Facades\App;


class ScrapingGetter
{
    protected $data_source;
    protected $url;

    public function __construct($data_source)
    {
        $this->data_source = $data_source;
        $this->url = $data_source['configuration']['url'];
    }

    public static function getSchoolNumber($string)
    {
        $string = rtrim($string);
        preg_match_all("/\d+/", $string, $new_string);
        $number = end($new_string[0]);
        return $number;
    }

    public static function getSchoolName($string)
    {
        $name = explode('School No', $string);
        $name = str_replace(',', '', $name[0]);
        return rtrim($name);
    }


    public static function getRevokedDate($string)
    {
        $revoked_date = explode('revoked effective', $string);
        $revoked_date = trim($revoked_date[1]);
        return $revoked_date;
    }

    public function storeScrapingSchool($array, $status)
    {
        $record = App::make(SchoolRecord::class);
        $array['status'] = $status;
        $school = $record->addSchool($array['number']);
        $school->addRevision($array, $this->data_source);
    }
}

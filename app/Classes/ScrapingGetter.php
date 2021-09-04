<?php 

namespace App\Classes;
use App\Models\School;
use Storage;
use App\Models\SchoolRevision;


class ScrapingGetter {

    public static function getSchoolNumber($string){
        $string = rtrim($string);
        preg_match_all("/\d+/", $string, $new_string);
        $number = end($new_string[0]);
        return $number;

    }



    public static function getSchoolName($string){
        $name = explode('School No', $string);
        $name = str_replace(',', '', $name[0]);
        return rtrim($name);
    }


    public static function getRevokedDate($string){
        $revoked_date = explode('revoked effective', $string);
        $revoked_date = trim($revoked_date[1]);
        return $revoked_date;

    }



    public static function storeScrapingSchool($array, $status){
        $array['status'] = $status;
        $school = School::updateOrCreate(['number'=>$array['number']]);
        $array['school_id'] = $school->id;
        SchoolRevision::create($array);

        $latest_ver = $school->getLatestVersion();
        $school->revision_id = $latest_ver->id;
        $school->status = $latest_ver->status;
        $school->save();

    }
    
}

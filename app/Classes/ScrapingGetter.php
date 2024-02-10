<?php

namespace App\Classes;

use App\Classes\SchoolRecord;
use Illuminate\Support\Facades\App;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;




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


    // public static function getRevokedDate($string)
    // {
    //     $revoked_date = explode('revoked effective', $string);
    //     $revoked_date = trim($revoked_date[1]);
    //     return \Carbon\Carbon::parse($revoked_date);
    // }

    public function storeScrapingSchool($array)
    {
        $record = App::make(SchoolRecord::class);

        $array['data_source_id'] = $this->data_source->id;
        $array['status'] = $this->data_source['configuration']['overrides']['status'];


        // $test_array = ["name" => "Wali ul Asr School-Niagara Campus",
        //               "number" => "888393",
        //               "address_line_1" => "8333 Willoughby Drive Niagara Falls, Ontario L2G 6X4",
        //               "street" => "8333 Willoughby Drive Niagara Falls",
        //               "postal_code" => "L2G 6X4",
        //               "principal_name" => "Zaidi Mohsin",
        //               "owner_business" => "Zaidi Syed",
        //               "closed_date" => "2018-19 ",
        //               "data_source_id" => 4,
        //               "status" => "closed"
        //           ];

        $school = $record->addSchool($array['number']);
        $school->addRevision($array, $this->data_source);
    }


    public function newCrawler()
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $this->url);
        $html = '' . $res->getBody();
        return $crawler = new Crawler($html);
    }



    public function dataSourceUpdate($checksum)
    {
        $this->data_source->update([
            'last_sync' => Carbon::now(),
            'checksum' => $checksum
        ]);
    }


    public function getPostalCode($long_address){
        $postal_code = substr($long_address, -8);
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', $postal_code);
    }



    public function getStreet($long_address){
        $street = explode(',', $long_address);
        // return $street[0];
        return str_replace("<br>", " ", $street[0]);
    }


}

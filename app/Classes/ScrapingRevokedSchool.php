<?php

namespace App\Classes;

use Symfony\Component\DomCrawler\Crawler;
use App\Classes\ScrapingGetter;
use Carbon\Carbon;




class ScrapingRevokedSchool extends ScrapingGetter
{

	public function start()
	{

		$crawler = $this->newCrawler();
		// $nodeValues = $crawler->filter('#right_column ol li');
        $whole_page_data = $crawler->filter('.body-field table tbody');
        $whole_page_data->each(function ($node) use (&$arr) {
			$arr[] = $node->html();
		});

        $html_checksum = md5(json_encode($arr));
		if ($this->data_source->checksum == $html_checksum) {
		} else {

			$whole_page_data = $crawler->filter('.body-field h3:contains("school year")');
			$this->scrapeAndStore($whole_page_data);
        		// var_dump($whole_page_data);	
		}


		$this->dataSourceUpdate($html_checksum);
	}


	public function scrapeAndStore($nodeValues)
	{

		$nodeValues->each(function ($node) {
			$table_content = $node->nextAll()->first()->filter('table tbody')->filter('tr')->each(function ($tr, $i) {
				return $tr->filter('td')->each(function ($td, $i) {
					if($i == 2) return trim($td->html());
					else return trim($td->text());
				});
			});


			foreach ($table_content as $value) {
				$scraper_school = [];
				$scraper_school['name'] = $value[0];
				$scraper_school['number'] = trim($value[1]);

				$scraper_school['address_line_1'] = $value[2];
				$scraper_school['street'] = $this->getStreet($value[2]);
				$scraper_school['postal_code'] = $this->getPostalCode($value[2]);

				$scraper_school['revoked_date'] = $this->getRevokedDate($value[3]);


				// var_dump($scraper_school);
				$this->storeScrapingSchool($scraper_school);
			}
		});
	}


	public function getPrincipalName($string)
	{
		if (str_contains($string, 'Principal')) { //to check if this row contain Principal
			$string = trim($string);
			$principal_name = explode('Principal: ', $string);
			$principal_name = $principal_name[1];
			return $principal_name;
		} else return '';
	}


	public function getOwnerBusiness($string)
	{
		if (str_contains($string, 'Owner')) { //to check if this row contain owner

			$string = trim($string);
			$owner_business = explode(':', $string);
			$owner_business = $owner_business[1];
			return trim($owner_business);
		} else return '';
	}


	public function getCity($string){
		$string = trim($string);
		$city = explode(',', $string);
		$city = $city[0];
		return trim($city);
	}




    private function getRevokedDate($value)
    {

	 	$clean_string = preg_replace('/[\s]+/mu', ' ', $value);//to remove double spaces

    	return Carbon::createFromFormat('M d, Y', $clean_string)->format('Y-m-d');

    }

}

<?php

namespace App\Classes;

use Symfony\Component\DomCrawler\Crawler;
use App\Classes\ScrapingGetter;
use Carbon\Carbon;
use App\Models\SchoolRevision;



class ScrapingClosedSchool extends ScrapingGetter
{


	public function start()
	{

		$crawler = $this->newCrawler();
		$whole_page_date = $crawler->filter('.flex-table table tbody');
		$whole_page_date->each(function ($node) use (&$arr) {
			$arr[] = $node->html();
		});

		$html_checksum = md5(json_encode($arr));
		if ($this->data_source->checksum == $html_checksum) {
		} else {

			$whole_page_date = $crawler->filter('.body-field h3:contains("school year")');
			$this->scrapeAndStore($whole_page_date);
		}

		$this->dataSourceUpdate($html_checksum);
	}


	public function scrapeAndStore($nodeValues)
	{

		$nodeValues->each(function ($node) {

			$closed_year = preg_replace("/[a-zA-Z]+/", '', $node->text());
			$table_content = $node->nextAll()->first()->filter('table tbody')->filter('tr')->each(function ($tr, $i) {
				return $tr->filter('td')->each(function ($td, $i) {
					if($i == 2) return trim($td->html());
					else return trim($td->text());
				});
			});

			// dd($table_content);

			foreach ($table_content as $value) {
				$scraper_school = [];
				$scraper_school['name'] = $value[0];
				$scraper_school['number'] = $this->getNumberFromString($value[1]);
				$scraper_school['address_line_1'] = $this->getAddress($value[2]);
				$scraper_school['address_line_2'] = $this->getCity($value[2]);
				$scraper_school['address_line_3'] = $this->getPostalCode($value[2]);
				$scraper_school['principal_name'] = $value[3];
				$scraper_school['owner_business'] = $value[4];
				$scraper_school['closed_date'] = $this->getClosingYear($closed_year);

				$this->storeScrapingSchool($scraper_school);

				// SchoolRevision::create([
				// 	'name' => $value[0],
				// 	'number' => $value[1],
				// 	'address_line_1' => utf8_decode($value[2]),
				// 	'principal_name' => $value[3],
				// 	'owner_business' => $value[4],
				// 	'hash' => 'asdasdas',
				// ]);
			}
		});
	}

	private function getNumberFromString($num_str)
	{
		$numbers = explode('&', $num_str);

		if (count($numbers) == 1)
			return $num_str;

		// todo fix this.
		return trim($numbers[1]);
	}

	private function getClosingYear($year_str)
	{
		return $year_str;
	}
	public function getPrincipalName($string)
	{
		if (str_contains($string, 'Principal')) {
			$principal_name = explode('Principal', $string);
			$principal_name = trim($principal_name[1]);
			if (str_contains($principal_name, 'Owner')) {
				$principal_name = explode('Owner', $principal_name);
				$principal_name = trim($principal_name[0]);
			}

			if (str_contains($principal_name, 'Direction')) {
				$principal_name = explode('Direction', $principal_name);
				$principal_name = trim($principal_name[0]);
			}

			return ltrim($principal_name, ': ');
		} else return '';
	}


	public function getOwnerBusiness($string)
	{
		if (str_contains($string, 'Owner') || str_contains($string, 'Direction')) {
			//to check if this row contain owner
			$owner_business = explode(':', $string);
			$owner_business = array_pop($owner_business);
			return ltrim($owner_business);
		} else return '';
	}


	public function getAddress($long_address){

		// $long_address = preg_split('/\r\n|\r|\n/', $long_address);
		$long_address = array_map('trim', explode(',', $long_address));

		$address = $long_address[0];
		return strip_tags($address);

	}


	public function getCity($long_address){
		// $long_address = preg_split('/\r\n|\r|\n/', $long_address);
		$long_address = array_map('trim', explode(',', $long_address));

		//there are two types of data; data with three parts (address,city,postal-code) and data with only two parts (address,postal-code)
		if(count($long_address) > 2 ) $city = $long_address[1]; 
		else $city = '';
		return strip_tags($city);
	}


	public function getPostalCode($long_address){
		// $long_address = preg_split('/\r\n|\r|\n/', $long_address);
		$long_address = array_map('trim', explode(',', $long_address));

		if(count($long_address) > 2 ) $postal_code = $long_address[2];
		else $postal_code = $long_address[1];
		return strip_tags($postal_code);
	}
}

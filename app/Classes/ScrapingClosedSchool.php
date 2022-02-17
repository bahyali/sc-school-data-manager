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
					return trim($td->text());
				});
			});

			foreach ($table_content as $value) {
				$scraper_school = [];
				$scraper_school['name'] = $value[0];
				$scraper_school['number'] = $this->getNumberFromString($value[1]);
				$scraper_school['address_line_1'] = $value[2];
				$scraper_school['principal_name'] = $value[3];
				$scraper_school['owner_business'] = $value[4];
				$scraper_school['closed_date'] = $closed_year;

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
}

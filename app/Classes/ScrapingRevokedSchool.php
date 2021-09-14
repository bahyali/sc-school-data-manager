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
		$nodeValues = $crawler->filter('#right_column ol li');
		return $this->scrapeAndStore($nodeValues);

	}


	public function scrapeAndStore($nodeValues){


		$arr = [];
		$nodeValues->each(function ($node) use (&$arr) {$arr[] = $node->html();});
		// $arr = $nodeValues->map(function ($node) { return $node->html();});
		
		$html_checksum = md5(json_encode($arr)); 


		if ($this->data_source->checksum == $html_checksum){
			// return 'This page scrapped before!';
		}

		else{
			$nodeValues->each(function ($node) {

				$tags = explode("\n", strip_tags($node->html()));
				$tags = array_filter($tags);

				$scraper_school = [];
				$scraper_school['name'] = $this->getSchoolName($tags[0]);
				$scraper_school['number'] = $this->getSchoolNumber($tags[0]);
				$scraper_school['address_line_1'] = trim($tags[1]);
				$scraper_school['address_line_2'] = trim($tags[2]);
				$scraper_school['revoked_date'] = $this->getRevokedDate($node->text());
				$scraper_school['principal_name'] = '';
				$scraper_school['owner_business'] = '';

				if (count($tags) == 6) {
					$scraper_school['principal_name'] = $this->getPrincipalName($tags[3]);
					$scraper_school['owner_business'] = $this->getOwnerBusiness($tags[4]);
				}


				if (count($tags) == 5) {
					$scraper_school['principal_name'] = $this->getPrincipalName($tags[3]);
					$scraper_school['owner_business'] = $this->getOwnerBusiness($tags[3]);
				}

				return $this->storeScrapingSchool($scraper_school);
			});
		}


		$this->data_source->update([
			'last_sync' => Carbon::now(),
			'checksum' => $html_checksum
		]);
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
}

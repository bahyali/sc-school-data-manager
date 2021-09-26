<?php

namespace App\Classes;

use Symfony\Component\DomCrawler\Crawler;
use App\Classes\ScrapingGetter;
use Carbon\Carbon;




class ScrapingClosedSchool extends ScrapingGetter
{

	public function start()
	{

		$crawler = $this->newCrawler();
		$nodeValues = $crawler->filter('#right_column ul')->first()->nextAll()->filter('li');
		return $this->scrapeAndStore($nodeValues);
	}

	public function scrapeAndStore($nodeValues)
	{

		$arr = [];
		$nodeValues->each(function ($node) use (&$arr) {
			$arr[] = $node->html();
		});
		$html_checksum = md5(json_encode($arr));

		if ($this->data_source->checksum == $html_checksum) {
			// return 'This page scrapped before!';
		} else {
			$nodeValues->each(function ($node) {

				$tags = explode("\n", strip_tags($node->html()));
				// $tags = array_filter($tags);
				$tags = array_filter($tags, function ($v) {
					return strlen($v) > 2;
				});
				$tags = array_values($tags);

				$scraper_school = [];
				$scraper_school['name'] = $this->getSchoolName($tags[0]);
				$scraper_school['number'] = $this->getSchoolNumber($tags[0]);
				$scraper_school['address_line_1'] = trim($tags[1]);
				$scraper_school['address_line_2'] = (count($tags) > 3) ? (str_contains($tags[2], 'Principal')) ? '' : trim($tags[2]) : '';
				$scraper_school['address_line_3'] = (count($tags) > 5) ? trim($tags[3]) : '';
				$scraper_school['principal_name'] = $this->getPrincipalName($node->text());
				$scraper_school['owner_business'] = $this->getOwnerBusiness($node->text());

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

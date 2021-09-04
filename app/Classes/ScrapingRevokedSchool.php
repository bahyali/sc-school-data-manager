<?php

namespace App\Classes;

use Symfony\Component\DomCrawler\Crawler;
use App\Classes\ScrapingGetter;



class ScrapingRevokedSchool extends ScrapingGetter
{
	protected $status = 'revoked';

	public function start()
	{

		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', $this->url);
		$html = '' . $res->getBody();

		$crawler = new Crawler($html);
		// var_dump($crawler);
		$nodeValues = $crawler->filter('#right_column ol li');

		// dd($nodeValues);


		$nodeValues->each(function ($node) {

			$tags = explode("\n", strip_tags($node->html()));
			$tags = array_filter($tags);

			$name = $this->getSchoolName($tags[0]);
			$number = $this->getSchoolNumber($tags[0]);
			$address_line_1 = trim($tags[1]);
			$address_line_2 = trim($tags[2]);
			$revoked_date = $this->getRevokedDate($node->text());
			$principal_name = '';
			$owner_business = '';

			if (count($tags) == 6) {
				$principal_name = $this->getPrincipalName($tags[3]);
				$owner_business = $this->getOwnerBusiness($tags[4]);
			}


			if (count($tags) == 5) {
				$principal_name = $this->getPrincipalName($tags[3]);
				$owner_business = $this->getOwnerBusiness($tags[3]);
			}


			$revoked_school = [
				'name' => $name,
				'number' => $number,
				'address_line_1' => $address_line_1,
				'address_line_2' => $address_line_2,
				'principal_name' => $principal_name,
				'owner_business' => $owner_business,
				'revoked_date' => $revoked_date,
				'data_source_id' => $this->data_source->id
			];

			return $this->storeScrapingSchool($revoked_school, $this->status);
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
}

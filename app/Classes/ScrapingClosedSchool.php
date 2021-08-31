<?php 

namespace App\Classes;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\School;
use App\Models\SchoolRevision;



class ScrapingClosedSchool {


    public function start() {

    	$url = 'http://www.edu.gov.on.ca/eng/general/elemsec/privsch/closed.html#2021';
    	$client = new \GuzzleHttp\Client();
    	$res = $client->request('GET', $url);
    	$html = ''.$res->getBody();

    	$crawler = new Crawler($html);
    	$nodeValues = $crawler->filter('#right_column ul')->first()->nextAll()->filter('li');

    	$nodeValues->each(function ($node) {

    	 	$tags = explode("\n", strip_tags($node->html()));
    	 	// $tags = array_filter($tags);
    	 	$tags = array_filter($tags,function($v){ return strlen($v) > 2; });
    	 	$tags = array_values($tags);

			$name = $this->getSchoolName($tags[0]);
			$number = $this->getSchoolNumber($tags[0]);
			$address_line_1 = trim($tags[1]);
			$address_line_2 = (count($tags) > 3) ? (str_contains($tags[2], 'Principal')) ? '' : trim($tags[2]) : '';
			$address_line_3 = (count($tags) > 5) ? trim($tags[3]): '';
			$principal_name = $this->getPrincipalName($node->text());
			$owner_business = $this->getOwnerBusiness($node->text());

			$closed_school = [
				'name' => $name,
			   	'number' =>$number, 
			   	'address_line_1' =>$address_line_1, 
			   	'address_line_2' =>$address_line_2, 
			   	'address_line_3' =>$address_line_3, 
			   	'principal_name' =>$principal_name, 
			   	'owner_business' =>$owner_business, 
			];
			return $this->storeClosedSchool($closed_school);

			

		});
    }



    public function getSchoolName($string){
		$name = explode('School No', $string);
		$name = str_replace(',', '', $name[0]);
		return rtrim($name);
	}



	public function getSchoolNumber($string){
		$string = rtrim($string);
	 	preg_match_all("/\d+/", $string, $new_string);
		$number = end($new_string[0]);
	    return $number;

	}


	public function getPrincipalName($string){
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
	 	
	    }
		else return '';

	}


	public function getOwnerBusiness($string){
		if (str_contains($string, 'Owner') || str_contains($string, 'Direction')) {
			//to check if this row contain owner
			$owner_business = explode(':', $string);
			$owner_business = array_pop($owner_business);
			return ltrim($owner_business);
		}

		else return '';
	}



	// public function storeClosedSchool($array){
 //        $array['status'] = 'closed';
 //        return $School = School::create($array);

	// }


	public function storeClosedSchool($array){
        $array['status'] = 'closed';
		$school = School::updateOrCreate(['number'=>$array['number']]);
        $array['school_id'] = $school->id;
        SchoolRevision::create($array);

        $latest_ver = $school->getLatestVersion();
        $school->revision_id = $latest_ver->id;
        $school->save();

	}
}
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;


class SchoolController extends Controller
{


    public function getSchools($status = NULL){
	  	if ($status) $schools = School::where('status', $status)->with('getSchool')->get();
	  	else $schools = School::with('getSchool')->get();

	 	return response()->json(['schools' => $schools]);
    }


    public function getSchoolsRevisions(){
	  	$schools = School::with('revisions')->get();
	 	return response()->json(['schools' => $schools]);
    }



    public function getOneSchool($school_id){
    	// return $school_id;
	  	$school = School::find($school_id);
	  	$school_details = $school->getSchool;
	  	$school_revisions = $school->revisions;
	 	return response()->json(['school' => $school_details, 'school_revisions' => $school_revisions]);
    }


   



}

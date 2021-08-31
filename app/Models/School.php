<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;
    protected $guarded = ['id'];


    public function revisions()
    {
        return $this->hasMany(SchoolRevision::class);
    }

    public function getLatestVersion(){
    	
	  return $revision = $this->revisions()->orderByRaw("FIELD(status , 'closed', 'active', 'revoked')")->latest()->first();
    }

}

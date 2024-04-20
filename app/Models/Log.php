<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    // protected $hidden = ['revision'];


    public function revision()
    {
        return $this->belongsTo(SchoolRevision::class);
    }


    //this is the same method as the revision() but we made it to prevent using revision in single log API
    public function getRevision()
    {
        return $this->belongsTo(SchoolRevision::class, 'revision_id');
    }



    public function school()
    {
        return $this->belongsTo(School::class);
    }

}

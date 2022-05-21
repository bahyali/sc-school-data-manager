<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataChange extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function affectedRecords()
    {
        return $this->belongsToMany(SchoolRevision::class, 'data_change_values', null, 'revision_id');
    }

    public function values()
    {
        return $this->hasMany(DataChangeValue::class);
    }


    public function getSchools()
    {
        return $this->affectedRecords->map(function ($revision) {
            return $revision->school()->with(['lastRevision', 'revisions', 'revisions.dataSource'])->first();
        })->unique();
    }


    public function school(){
        return $this->belongsTo(School::class);
    }
}

// class DataChangeValue extends Model
// {
//     use HasFactory;

//     protected $guarded = ['id'];

//     public function dataChange()
//     {
//         return $this->belongsTo(DataChange::class);
//     }

//     public function affectedRecord()
//     {
//         return $this->belongsTo(SchoolRevision::class, 'revision_id');
//     }
// }

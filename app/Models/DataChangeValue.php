<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DataChangeValue extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function dataChange()
    {
        return $this->belongsTo(DataChange::class);
    }

    public function affectedRecord()
    {
        return $this->belongsTo(SchoolRevision::class, 'revision_id');
    }
}

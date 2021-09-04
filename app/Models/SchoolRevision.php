<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolRevision extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    // protected $dates = ['revoked_date', 'closed_date', 'open_date'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = strtolower($value);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function scopeByDataSourceId($query, $data_source_id)
    {
        return $query->where('data_source_id', $data_source_id);
    }
}

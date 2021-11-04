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

    public function lastRevision()
    {
        return $this->belongsTo(SchoolRevision::class, 'revision_id');
    }

    public function dataSources()
    {
        return $this->belongsToMany(DataSource::class, 'school_revisions');
    }

    public function getLatestVersion()
    {

        return $this->revisions()->orderByRaw("FIELD(status , 'closed', 'active', 'revoked')")->latest()->first();
    }


    public function getSchool()
    {
        return $this->belongsTo(SchoolRevision::class, 'revision_id');
    }
}

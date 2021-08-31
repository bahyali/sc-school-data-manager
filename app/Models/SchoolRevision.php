<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolRevision extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    // protected $dates = ['revoked_date', 'closed_date', 'open_date'];
    
}

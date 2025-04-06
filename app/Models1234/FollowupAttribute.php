<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowupAttribute extends Model
{
    use HasFactory;
    protected $table = "candidate_followup_status_details";
    public $timestamps = false;
}

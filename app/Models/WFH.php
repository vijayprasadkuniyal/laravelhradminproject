<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WFH extends Model
{
    use HasFactory;
    protected $table = "work_from_home";
    public $timestamps = false;
}

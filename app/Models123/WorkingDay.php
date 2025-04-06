<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
    use HasFactory;
    protected $table = 'create_working_day';
    public $timestamps = false;
}

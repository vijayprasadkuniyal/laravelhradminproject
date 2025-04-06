<?php

namespace App\Models\commonModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locality extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection= 'sales_db';
    protected $table = 'locality';
}

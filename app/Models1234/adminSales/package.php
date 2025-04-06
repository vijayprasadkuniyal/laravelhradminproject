<?php

namespace App\Models\adminSales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection= 'sales_db';
    protected $table = 'dure_and_pkg_type_fact';
}

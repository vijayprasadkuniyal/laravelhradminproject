<?php

namespace App\Models\adminSales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead_fact extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection= 'sales_db';
    protected $table = 'lead_factor';
}

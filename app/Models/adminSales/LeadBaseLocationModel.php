<?php

namespace App\Models\adminSales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadBaseLocationModel extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection= 'sales_db';
    protected $table = 'lead_base_location_condition';
    
}

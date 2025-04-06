<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyTarget extends Model
{
    use HasFactory;
    protected $table = 'save_company_target';
    protected $fillable = ['attribute_id','group_id','category_id','department_id','value','created_by','financial_year'];
    public $timestamps = false;
    //protected $fillable = ['name', 'email', 'phone'];        


}

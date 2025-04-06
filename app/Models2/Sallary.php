<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sallary extends Model
{
    use HasFactory;
    protected $table = 'emp_salary_info';
     public $timestamps = false;
}

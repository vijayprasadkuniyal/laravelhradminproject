<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryHistory extends Model
{
    protected $table = "emp_salary_history";
    public $timestamp = "false";
    use HasFactory;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveBlance extends Model
{
    use HasFactory;
    protected $table = 'emp_leave_blance';
    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $table = 'attendance';
    public $timestamps = false;
    protected $fillable = ['attendance_date','emp_id','weekday','leave_status','leave_type','is_half_day','login_date_time'];
}

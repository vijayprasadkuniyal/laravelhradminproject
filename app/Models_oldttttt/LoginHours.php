<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHours extends Model
{
    use HasFactory;
    protected $table = "login_hours_calculate";
    public $timestamps = false;
}

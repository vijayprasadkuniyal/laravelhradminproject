<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignStock extends Model
{
    use HasFactory;
    protected $table = 'stock_assign';
    public $timestamps = false;
    protected $fillable = ['department_id','category_id','assign_to','stock_id','quantity','assign_date','created_by'];
}

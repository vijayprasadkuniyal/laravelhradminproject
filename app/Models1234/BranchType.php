<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchType extends Model
{
    use HasFactory;
    protected $table = 'branch_type';
    public $timestamps = false;
}

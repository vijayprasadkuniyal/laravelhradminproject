<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDocument extends Model
{
    use HasFactory;
    protected $table = 'sales_document';
    public $timestamps = false;
}

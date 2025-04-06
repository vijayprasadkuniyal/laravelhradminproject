<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $table = 'emp_document_info';
    public $timestamps = false;
    protected $fillable = ['doc_id','emp_id','document_details'];
}

<?php

namespace App\Models\ThirdPartyApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleMatrixKeyModel extends Model
{
    use HasFactory;
    protected $connection = 'sales_db';
    protected $table = "google_key_matrix";
}

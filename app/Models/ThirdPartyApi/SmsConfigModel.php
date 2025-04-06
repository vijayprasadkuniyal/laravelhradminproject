<?php

namespace App\Models\ThirdPartyApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsConfigModel extends Model
{
    use HasFactory;
    protected $connection = 'sales_db';
    protected $table = "sms_config";
}

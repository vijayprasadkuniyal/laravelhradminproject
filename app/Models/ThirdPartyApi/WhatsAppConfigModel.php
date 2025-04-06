<?php

namespace App\Models\ThirdPartyApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppConfigModel extends Model
{
    use HasFactory;
    protected $connection = 'sales_db';
    protected $table = "whatsapp_api";
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaiseAssetRequest extends Model
{
    use HasFactory;
    protected $table = 'raise_asset_request';
    public $timestamps = false;
}

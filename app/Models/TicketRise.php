<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketRise extends Model
{
    protected $table = 'ticket_rise';
    public $timestamps = false;
    use HasFactory;
}

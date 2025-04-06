<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;

class BasicInfo extends Authenticatable
{
    use HasApiTokens, Notifiable,HasFactory;
    protected $table = "emp_basic_info";
    public $timestamps = false;
    public function manager(){
    	return $this->belongsTo(BasicInfo::class,'reporting_manager');
    }
    public function allManagers(){
    	//return $this->manager?this->manager->higherManagers->concat([$this->manager]) : collect();
    	return $this->hasMany(Employee::class,'id','reporting_manager')->with('allManagers');
    }


}
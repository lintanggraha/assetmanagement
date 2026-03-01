<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'bank';

    protected $fillable = ['nama'];

    public function aplikasi(){
        return $this->hasMany('App\Aplikasi');
    }

    public function database(){
        return $this->hasMany('App\Database');
    }

    public function assets(){
        return $this->hasMany('App\Asset');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dokumentasi extends Model
{
    protected $table = 'dokumentasi';

    protected $fillable = ['judul','isi','user_id'];

    public function user(){
        return $this->belongsTo('App\User');
    }
}

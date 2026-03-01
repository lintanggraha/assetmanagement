<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faq';

    protected $fillable = ['judul','isi','user_id'];

    public function user(){
        return $this->belongsTo('App\User');
    }

}

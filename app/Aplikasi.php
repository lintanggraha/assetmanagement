<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Aplikasi extends Model
{
    protected $table = 'aplikasi';

    protected $fillable = ['jenis','nama','war','ip','port','bank_id','user_id'];

    public function bank(){
        return $this->belongsTo('App\Bank');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }
}

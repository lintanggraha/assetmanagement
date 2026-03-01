<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Database extends Model
{
    protected $table = 'database';

    protected $fillable = ['sistemoperasi','nama','jenis','ip','port','bank_id','user_id','deskripsi'];

    public function bank(){
        return $this->belongsTo('App\Bank');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }
}

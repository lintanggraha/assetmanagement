<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetActivityLog extends Model
{
    protected $table = 'asset_activity_logs';

    protected $fillable = [
        'asset_id',
        'actor_user_id',
        'action',
        'message',
        'context',
    ];

    public function asset()
    {
        return $this->belongsTo('App\Asset', 'asset_id');
    }

    public function actor()
    {
        return $this->belongsTo('App\User', 'actor_user_id');
    }
}


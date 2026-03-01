<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetPolicyViolation extends Model
{
    protected $table = 'asset_policy_violations';

    protected $fillable = [
        'asset_id',
        'user_id',
        'policy_code',
        'severity',
        'status',
        'message',
        'detected_at',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo('App\Asset', 'asset_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}


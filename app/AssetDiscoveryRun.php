<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetDiscoveryRun extends Model
{
    protected $table = 'asset_discovery_runs';

    protected $fillable = [
        'run_uuid',
        'scope',
        'source_mode',
        'status',
        'started_at',
        'completed_at',
        'total_found',
        'total_new',
        'total_updated',
        'total_matched',
        'summary',
        'input_payload',
        'user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function findings()
    {
        return $this->hasMany('App\AssetDiscoveryFinding', 'run_id');
    }
}


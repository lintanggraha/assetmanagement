<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetDiscoveryFinding extends Model
{
    protected $table = 'asset_discovery_findings';

    protected $fillable = [
        'run_id',
        'asset_id',
        'fingerprint',
        'asset_name',
        'asset_type',
        'ip_address',
        'hostname',
        'port',
        'environment',
        'finding_status',
        'confidence',
        'payload',
    ];

    public function run()
    {
        return $this->belongsTo('App\AssetDiscoveryRun', 'run_id');
    }

    public function asset()
    {
        return $this->belongsTo('App\Asset', 'asset_id');
    }
}


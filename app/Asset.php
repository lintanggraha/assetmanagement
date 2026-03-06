<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'asset_code',
        'name',
        'asset_type',
        'host_type',
        'server_role',
        'environment',
        'criticality',
        'status',
        'lifecycle_stage',
        'owner_name',
        'owner_email',
        'bank_id',
        'ip_address',
        'hostname',
        'operating_system',
        'os_version',
        'os_eol_date',
        'port',
        'source',
        'discovery_confidence',
        'risk_score',
        'tags',
        'last_seen_at',
        'notes',
        'asset_profile',
        'user_id',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'os_eol_date' => 'date',
        'asset_profile' => 'array',
    ];

    public function bank()
    {
        return $this->belongsTo('App\Bank');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function discoveryFindings()
    {
        return $this->hasMany('App\AssetDiscoveryFinding', 'asset_id');
    }

    public function activityLogs()
    {
        return $this->hasMany('App\AssetActivityLog', 'asset_id')->latest();
    }

    public function changeRequests()
    {
        return $this->hasMany('App\AssetChangeRequest', 'asset_id')->latest();
    }

    public function policyViolations()
    {
        return $this->hasMany('App\AssetPolicyViolation', 'asset_id');
    }

    public function services()
    {
        return $this->hasMany('App\AssetService', 'asset_id')->orderBy('is_primary', 'desc')->orderBy('service_name');
    }
}

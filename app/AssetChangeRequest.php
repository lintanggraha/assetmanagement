<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetChangeRequest extends Model
{
    protected $table = 'asset_change_requests';

    protected $fillable = [
        'asset_id',
        'requester_user_id',
        'approver_user_id',
        'change_type',
        'status',
        'reason',
        'current_snapshot',
        'requested_payload',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo('App\Asset', 'asset_id');
    }

    public function requester()
    {
        return $this->belongsTo('App\User', 'requester_user_id');
    }

    public function approver()
    {
        return $this->belongsTo('App\User', 'approver_user_id');
    }
}


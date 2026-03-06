<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AssetService extends Model
{
    protected $table = 'asset_services';

    protected $fillable = [
        'asset_id',
        'service_name',
        'service_type',
        'technology_stack',
        'version',
        'status',
        'port',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo('App\Asset', 'asset_id');
    }
}


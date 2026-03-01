<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function aplikasi(){
        return $this->hasMany('App\Aplikasi');
    }

    public function faq(){
        return $this->hasMany('App\Faq');
    }

    public function dokumentasi(){
        return $this->hasMany('App\Dokumentasi');
    }

    public function database(){
        return $this->hasMany('App\Database');
    }

    public function assets(){
        return $this->hasMany('App\Asset');
    }

    public function assetDiscoveryRuns(){
        return $this->hasMany('App\AssetDiscoveryRun');
    }

    public function assetActivityLogs(){
        return $this->hasMany('App\AssetActivityLog', 'actor_user_id');
    }

    public function requestedAssetChanges(){
        return $this->hasMany('App\AssetChangeRequest', 'requester_user_id');
    }

    public function approvedAssetChanges(){
        return $this->hasMany('App\AssetChangeRequest', 'approver_user_id');
    }

    public function assetPolicyViolations(){
        return $this->hasMany('App\AssetPolicyViolation');
    }

    public function hasRole($roles){
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($this->role, $roles);
    }

    public function isSuperAdmin(){
        return $this->role === 'superadmin';
    }

    public function isAdmin(){
        return $this->hasRole(['superadmin', 'admin']);
    }

    public function canApproveAssetChanges(){
        return $this->hasRole(['superadmin', 'admin', 'auditor']);
    }

    public function canManageUsers(){
        return $this->hasRole(['superadmin', 'admin']);
    }

    public function canRunDiscovery(){
        return $this->hasRole(['superadmin', 'admin', 'operator']);
    }

    public function canManageAssetRecords(){
        return $this->hasRole(['superadmin', 'admin', 'operator']);
    }

    public function hasGlobalAssetVisibility(){
        return $this->hasRole(['superadmin', 'admin', 'auditor']);
    }

}

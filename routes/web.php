<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/blank', function () {
    return view('blank');
});

Route::group(['middleware' => ['auth', 'active']], function () {

    Route::get('/', 'AssetOpsController@index')->name('asset-ops.index');
    Route::get('/dashboard', 'AssetOpsController@index')->name('dashboard');
    Route::get('/user-guide', function () {
        return view('guide.index');
    })->name('guide.index');

    Route::resource('asset-inventory', 'AssetController')->names('assets');
    Route::get('/discovery', 'DiscoveryController@index')->name('discovery.index');
    Route::post('/discovery/run', 'DiscoveryController@run')
        ->middleware('role:superadmin,admin,operator')
        ->name('discovery.run');
    Route::get('/discovery/{id}', 'DiscoveryController@show')->name('discovery.show');

    Route::get('/approvals', 'AssetApprovalController@index')
        ->middleware('role:superadmin,admin,auditor')
        ->name('approvals.index');
    Route::post('/approvals/{id}/approve', 'AssetApprovalController@approve')
        ->middleware('role:superadmin,admin,auditor')
        ->name('approvals.approve');
    Route::post('/approvals/{id}/reject', 'AssetApprovalController@reject')
        ->middleware('role:superadmin,admin,auditor')
        ->name('approvals.reject');

    Route::get('/asset-policies', 'AssetPolicyController@index')
        ->middleware('role:superadmin,admin,auditor,operator,viewer')
        ->name('policies.index');
    Route::post('/asset-policies/{id}/resolve', 'AssetPolicyController@resolve')
        ->middleware('role:superadmin,admin,auditor')
        ->name('policies.resolve');

    Route::get('/users', 'UserManagementController@index')
        ->middleware('role:superadmin,admin')
        ->name('users.index');
    Route::post('/users/{id}', 'UserManagementController@update')
        ->middleware('role:superadmin,admin')
        ->name('users.update');
});

Auth::routes();

Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

<?php

use Illuminate\Http\Request;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/CreatePackages','ApiPushController@mainMethod');
Route::get('/Push/{token}','ApiPushController@saveDevice');

Route::post('/v1/pushPackages/web.com.propiedadesmexico', 'ApiPushController@mainMethod');
Route::post('/v2/pushPackages/web.com.propiedadesmexico', 'ApiPushController@mainMethod');

Route::post('/v1/log', 'ApiPushController@log');
Route::post('/v2/log', 'ApiPushController@log');

Route::post('/v1/devices/{token}/registrations/web.com.propiedadesmexico', 'ApiPushController@saveDevice');
Route::post('/v2/devices/{token}/registrations/web.com.propiedadesmexico', 'ApiPushController@saveDevice');
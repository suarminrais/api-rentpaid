<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    // Matches "/api/register
    // $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    // $router->get('user', 'TenantController@user');
    
    //tenant routes
    // $router->get('tenant', 'TenantController@index');
    $router->get('tenant/{id}', 'TenantController@show');
    $router->get('kode/{tenant}', 'TenantController@find');
    $router->post('tenant', 'TenantController@search');

    //tunggakan routes
    $router->get('tunggakan', 'TransaksiController@tunggakan');
    $router->post('tunggakan', 'TransaksiController@bayar');
    $router->post('search', 'TransaksiController@search');
    $router->get('tunggakan/{id}', 'TransaksiController@tunggakanSingle');

    //akhir sesi
    $router->post('sesi', 'TransaksiController@sesi');

    //transaksi route
    $router->post('transaksi', 'TransaksiController@store');

    //penagihan route
    $router->get('tagihan', 'TagihController@index');
});
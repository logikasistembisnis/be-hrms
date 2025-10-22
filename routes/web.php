<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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
    return 'Lumen API is running 🚀';
});

$router->post('/signup', 'AuthController@signup');

$router->group(['middleware' => 'auth:api'], function () use ($router) {
    $router->get('/me', 'AuthController@me');
    $router->post('/logout', 'AuthController@logout');
});

$router->get('/companydesign', 'CompanyDesignController@index');
    $router->get('/country', 'CountryController@index');

    $router->get('/company', 'CompanyController@index');
    $router->post('/company', 'CompanyController@store');
    $router->post('/company/update/{id}', 'CompanyController@update');
    $router->put('/company/{id}', 'CompanyController@update');
    $router->delete('/company/{id}', 'CompanyController@destroy');
    
    $router->get('/storage/{path:.*}', function ($path) {
        $filePath = storage_path('app/public/' . $path);
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        return response()->file($filePath);
    });
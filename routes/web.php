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
$router->put('/company', 'CompanyController@upsertCompany');
$router->post('/company/{id}/details', 'CompanyController@updateDetails');
$router->put('/company/companydesign', 'CompanyController@updateDesignAndReportTo');
$router->delete('/company/{id}', 'CompanyController@destroyCompany');
    
$router->get('/storage/{path:.*}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404, 'File not found');
    }
    return response()->file($filePath);
});

$router->get('/tenant', 'TenantController@index');
$router->get('/tenant/{id}', 'TenantController@show');
$router->put('/tenant', 'TenantController@upsert');
$router->delete('/tenant/{id}', 'TenantController@destroy');

$router->get('/baseorgstructure', 'BaseOrgStructureController@index');

$router->get('/companybaseorgstruc', 'CompanyBaseOrgStrucController@index');
$router->put('/companybaseorgstruc', 'CompanyBaseOrgStrucController@upsertCompanyBaseOrgStruc');

$router->get('/hrbaserule', 'HRBaseRuleController@index');

$router->get('/companyhrrule', 'CompanyHRRuleController@index');
$router->put('/companyhrrule', 'CompanyHRRuleController@upsertCompanyHRRule');

$router->get('/daftarcuti', 'DaftarCutiController@index');
$router->get('/daftarizin', 'DaftarIzinController@index');

$router->get('/companycuti', 'CompanyCutiController@index');
$router->put('/companycuti', 'CompanyCutiController@upsertCompanyCuti');

$router->get('/companyizin', 'CompanyIzinController@index');
$router->put('/companyizin', 'CompanyIzinController@upsertCompanyIzin');

$router->get('/hariliburnasional', 'HariLiburNasionalController@index');
$router->get('/syncliburnasional', 'HariLiburNasionalController@fetchHariLibur');

$router->get('/compliburnasional', 'CompLiburNasionalController@index');
$router->post('/compliburnasional', 'CompLiburNasionalController@upsertCompLiburNasional');
$router->delete('/compliburnasional/{id}', 'CompLiburNasionalController@destroy');

$router->get('/companyworkinghours', 'CompanyWorkingHoursController@index');
$router->put('/companyworkinghours', 'CompanyWorkingHoursController@upsertCompanyWorkingHours');
$router->delete('/companyworkinghours/{id}', 'CompanyWorkingHoursController@destroy');

$router->get('/companyworkingbreaktime', 'CompanyWorkingBreaktimeController@index');
$router->put('/companyworkingbreaktime', 'CompanyWorkingBreaktimeController@upsertCompanyWorkingBreaktime');
$router->delete('/companyworkingbreaktime/{id}', 'CompanyWorkingBreaktimeController@destroy');

$router->get('/grouprole', 'GroupRoleController@index');
$router->get('/menu', 'MenuController@index');
$router->put('/menu', 'MenuController@upsert');
$router->delete('/menu/{id}', 'MenuController@destroy');

$router->get('/organization', 'OrganizationController@index');
$router->post('/organization', 'OrganizationController@insertHierarchy');
$router->post('/organization/nonactive', 'OrganizationController@nonactiveHierarchy');

$router->get('/jobfamily', 'JobFamilyController@index');
$router->post('/jobfamily', 'JobFamilyController@insert');
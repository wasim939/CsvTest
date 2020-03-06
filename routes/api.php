<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('csv-data', 'HomeController@cacheCsvData');
Route::post('my-xml', 'HomeController@myXml');
Route::post('media', 'HomeController@hotelMedia');
Route::post('rate', 'HomeController@hotelRateInfo');
Route::post('rule', 'HomeController@hotelRuleInfo');
Route::get('guzzle', 'HomeController@testGuzzle');

Route::prefix('v1')->group(function () {
    Route::post('my-test', 'API\V1\MyHotelController@test');

    Route::post('search', 'API\V1\MyHotelController@hotelSearch');
    Route::post('media', 'API\V1\MyHotelController@hotelMedia');
    Route::post('rate', 'API\V1\MyHotelController@hotelRateInfo');
    Route::post('rule', 'API\V1\MyHotelController@hotelRuleInfo');
    Route::post('description', 'API\V1\MyHotelController@hotelDescInfo');
});




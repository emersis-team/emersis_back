<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::group([
    'middleware' => ['JSONMiddleware'],
    'prefix' => 'v1',
    'namespace' => 'API',
    'name' => 'api.',
], function () {

    Route::prefix('novedades')->group(function () {
        Route::get('', 'NovedadesBackController@getNovedades');
        Route::post('', 'NovedadesBackController@createNovedad');
        Route::patch('/{novedad}', 'NovedadesBackController@updateNovedad');
        Route::delete('/{novedad}', 'NovedadesBackController@deleteNovedad');

        Route::get('/{novedad}/files', 'NovedadesBackController@getFilesFromNovedad');
        Route::post('/{novedad}/files', 'NovedadesBackController@createFiles');

      //  Route::post('login', 'AuthController@login');
    });

    Route::prefix('auth')->group(function () {
        Route::post('login', 'AuthController@login');
        // Route::post('logout', 'AuthController@logout');
        // Route::post('refresh', 'AuthController@refresh');
        // Route::post('me', 'AuthController@me');
    });

});

Route::group([
    'middleware' => ['jwt.auth'],
    'prefix' => 'v1',
    'namespace' => 'API',
    'name' => 'api.',

], function () {
    Route::prefix('messages')->group(function () {
        Route::get('/', 'MessagesController@getConversations');
        Route::get('/{conversation}', 'MessagesController@getMessagesFromUser');
        Route::post('/', 'MessagesController@createMessage');
    });
 });



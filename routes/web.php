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

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');
Route::get('/user/trackClick/', 'HomeController@trackClick');
Route::post('/importCsv', 'ContactController@parseImport')->name('importCsv');
Route::get('/{any}', 'HomeController@index')->name('home');
Route::resource('contacts', 'ContactController')->only(['store', 'edit', 'update']);

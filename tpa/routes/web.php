<?php

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

//Route::get('/show/{id}','Datacollector@show');

Route::get('/twinfieldTest', 'TFcollector@tfData');

Route::get('/twinfieldData', 'Datacollector@twinfieldData');

Route::get('/quickbookData', 'Datacollector@quickbookData');

Route::get('/clearbookData', 'Datacollector@clearbookData');

Route::get('/freshbookData', 'Datacollector@freshbookData');

Route::get('/kashflowData', 'Datacollector@kashflowData');

Route::get('/redirectToXero', 'Datacollector@redirectToXero');

Route::get('/freshbookDataTest', 'Datacollector@freshbookDataTest1');

Route::get('/sageoneData', 'Datacollector@sageOneRecords');

Route::get('/xeroData', 'Datacollector@xeroData');

Route::get('/xeroWebhooks', 'Datacollector@xeroWebhooks');

Route::get('/welcomeToXero', 'Datacollector@welcomeToXero');

Route::get('/exactDataNL', 'Datacollector@exactData');

Route::get('/exactDataUK', 'Datacollector@exactDataUK');

Route::get('/reeleezeeData', 'Datacollector@reeleezeeData');

Route::get('/freeagentData', 'Datacollector@freeagentData');

Route::get('/freeagentGetTokens', 'Datacollector@freeagentGetTokens');

//Route::get('/create', 'Users@create');

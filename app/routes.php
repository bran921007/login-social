<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('login');
});

Route::get('twitterAuth/{auth?}',array('as'=>'twitterAuth','uses'=>'AuthController@getTwitterLogin'));

Route::get('fbauth/{auth?}',array('as'=>'facebookAuth','uses'=>'AuthController@getFacebookLogin'));


Route::get('gauth/{auth?}',array('as'=>'googleAuth','uses'=>'AuthController@getGoogleLogin'));


Route::get('logout',array('as'=>'logout','uses'=>'AuthController@getLoggedOut'));
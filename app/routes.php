<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('login'));
Route::get('twitterAuth/{auth?}', [AuthController::class, 'getTwitterLogin'])->where('auth', 'auth')->name('twitterAuth');
Route::get('fbauth/{auth?}', [AuthController::class, 'getFacebookLogin'])->where('auth', 'auth')->name('facebookAuth');
Route::get('gauth/{auth?}', [AuthController::class, 'getGoogleLogin'])->where('auth', 'auth')->name('googleAuth');
Route::get('logout', [AuthController::class, 'getLoggedOut'])->name('logout');

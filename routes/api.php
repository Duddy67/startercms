<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Blog\PostController;

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

// Allows unauthenticated users to access the public posts.
Route::get('posts', [PostController::class, 'index']);

Route::group(['middleware' => 'auth:api'], function () {
    // Users must be authenticated to access these methods.
    Route::apiResource('posts', PostController::class)->except(['index']);
});


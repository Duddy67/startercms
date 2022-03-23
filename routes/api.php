<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Blog\PostController;
use App\Http\Controllers\Api\AuthController;

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

Route::post('/login', [AuthController::class, 'login']);

// Allows unauthenticated users to access the public posts.
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{post}', [PostController::class, 'show']);

Route::group(['middleware' => 'auth:api'], function () {
    // Users must be authenticated to access these methods.
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
});


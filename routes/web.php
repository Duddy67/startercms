<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\UserGroupsController;

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

Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
});

Route::prefix('admin')->group(function () {

    Route::middleware(['admin'])->group(function () {
	Route::get('/', [AdminController::class, 'index'])->name('admin');
	// Users
	Route::delete('/users', [UsersController::class, 'massDestroy'])->name('admin.users.massDestroy');
	Route::resource('users', UsersController::class, ['as' => 'admin'])->except(['show']);
	// Roles
	Route::delete('/roles', [RolesController::class, 'massDestroy'])->name('admin.roles.massDestroy');
	Route::resource('roles', RolesController::class, ['as' => 'admin'])->except(['show']);
	// Permissions
	Route::get('/permissions', [PermissionsController::class, 'index'])->name('admin.permissions.index');
	Route::patch('/permissions', [PermissionsController::class, 'refresh'])->name('admin.permissions.refresh');
	Route::put('/permissions', [PermissionsController::class, 'reset'])->name('admin.permissions.reset');
	// UserGroups
	Route::delete('/usergroups', [UserGroupsController::class, 'massDestroy'])->name('admin.usergroups.massDestroy');
	Route::resource('usergroups', UserGroupsController::class, ['as' => 'admin'])->except(['show']);
    });
});


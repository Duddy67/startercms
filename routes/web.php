<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Users\RoleController;
use App\Http\Controllers\Admin\Users\PermissionController;
use App\Http\Controllers\Admin\Users\GroupController;
use App\Http\Controllers\Admin\Settings\GeneralController;

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

	Route::prefix('users')->group(function () {
	    // Users
	    Route::delete('/users', [UserController::class, 'massDestroy'])->name('admin.users.users.massDestroy');
	    Route::resource('users', UserController::class, ['as' => 'admin.users'])->except(['show']);
	    // Groups
	    Route::delete('/groups', [GroupController::class, 'massDestroy'])->name('admin.users.groups.massDestroy');
	    Route::resource('groups', GroupController::class, ['as' => 'admin.users'])->except(['show']);
	    // Roles
	    Route::delete('/roles', [RoleController::class, 'massDestroy'])->name('admin.users.roles.massDestroy');
	    Route::resource('roles', RoleController::class, ['as' => 'admin.users'])->except(['show']);
	    // Permissions
	    Route::get('/permissions', [PermissionController::class, 'index'])->name('admin.users.permissions.index');
	    Route::patch('/permissions', [PermissionController::class, 'build'])->name('admin.users.permissions.build');
	    Route::put('/permissions', [PermissionController::class, 'rebuild'])->name('admin.users.permissions.rebuild');
	});

	Route::prefix('settings')->group(function () {
	    Route::get('/general', [GeneralController::class, 'index'])->name('admin.settings.general.index');
	    Route::patch('/general', [GeneralController::class, 'update'])->name('admin.settings.general.update');
	});
    });
});


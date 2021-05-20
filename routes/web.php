<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\UserController;

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
	//Route::get('/users', [UsersController::class, 'index'])->name('admin.users');
	Route::delete('/users', [UsersController::class, 'massDestroy'])->name('admin.users.massDestroy');
	Route::resource('users', UsersController::class, ['as' => 'admin'])->except(['show']);
	/*Route::get('/users/{id}', [UserController::class, 'edit'])->where('id', '^[1-9][1-9]{0,}')->name('admin.users.edit');
	Route::post('/users/{id}', [UserController::class, 'update'])->where('id', '^[1-9][1-9]{0,}')->name('admin.users.update');
	Route::delete('/users/{id}', [UserController::class, 'destroy'])->where('id', '^[1-9][1-9]{0,}')->name('admin.users.destroy');
	Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
	Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');*/
    });
});


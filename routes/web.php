<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Cms\FileManagerController;
use App\Http\Controllers\Cms\FileController;
use App\Http\Controllers\Blog\PostController;
use App\Http\Controllers\Blog\CategoryController as BlogCategoryController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TokenController;

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

Route::get('/', [SiteController::class, 'index'])->name('site.index');

Route::get('/post/{id}/{slug}', [PostController::class, 'show'])->name('blog.post');
Route::get('/category/{id}/{slug}', [BlogCategoryController::class, 'index'])->name('blog.category');

Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/profile/token', [TokenController::class, 'update'])->name('profile.token');

Route::get('/cms/filemanager', [FileManagerController::class, 'index'])->name('cms.filemanager.index');
Route::post('/cms/filemanager', [FileManagerController::class, 'upload']);
Route::delete('/cms/filemanager', [FileManagerController::class, 'destroy'])->name('cms.filemanager.destroy');

Route::get('/expired', function () {
    return view('cms.filemanager.expired');
})->name('expired');

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

        // Files
        Route::get('/files', [FileController::class, 'index'])->name('admin.files.index');
        Route::delete('/files', [FileController::class, 'massDestroy'])->name('admin.files.massDestroy');
        Route::get('/files/batch', [FileController::class, 'batch'])->name('admin.files.batch');
        Route::put('/files/batch', [FileController::class, 'massUpdate'])->name('admin.files.massUpdate');

        Route::prefix('user')->group(__DIR__.'/admin/user.php');
        Route::prefix('blog')->group(__DIR__.'/admin/blog.php');
        Route::prefix('menus')->group(__DIR__.'/admin/menus.php');
        Route::prefix('settings')->group(__DIR__.'/admin/settings.php');
    });
});

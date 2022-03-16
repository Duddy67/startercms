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
use App\Http\Controllers\Admin\Settings\EmailController;
use App\Http\Controllers\Cms\FileManagerController;
use App\Http\Controllers\Cms\FileController;
use App\Http\Controllers\Admin\Blog\PostController as AdminPostController;
use App\Http\Controllers\Admin\Blog\CategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Blog\PostController;
use App\Http\Controllers\Blog\CategoryController as BlogCategoryController;
use App\Http\Controllers\Admin\Blog\SettingController as AdminBlogSettingController;
use App\Http\Controllers\Admin\Menus\MenuController;
use App\Http\Controllers\Admin\Menus\MenuItemController;
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

/*Route::get('/', function () {
    return view('welcome');
});*/

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

	Route::prefix('users')->group(function () {
	    // Users
	    Route::delete('/users', [UserController::class, 'massDestroy'])->name('admin.users.users.massDestroy');
	    Route::get('/users/batch', [UserController::class, 'batch'])->name('admin.users.users.batch');
	    Route::put('/users/batch', [UserController::class, 'massUpdate'])->name('admin.users.users.massUpdate');
	    Route::get('/users/cancel/{user?}', [UserController::class, 'cancel'])->name('admin.users.users.cancel');
	    Route::put('/users/checkin', [UserController::class, 'massCheckIn'])->name('admin.users.users.massCheckIn');
	    Route::resource('users', UserController::class, ['as' => 'admin.users'])->except(['show']);
	    // Groups
	    Route::delete('/groups', [GroupController::class, 'massDestroy'])->name('admin.users.groups.massDestroy');
	    Route::get('/groups/batch', [GroupController::class, 'batch'])->name('admin.users.groups.batch');
	    Route::put('/groups/batch', [GroupController::class, 'massUpdate'])->name('admin.users.groups.massUpdate');
	    Route::get('/groups/cancel/{group?}', [GroupController::class, 'cancel'])->name('admin.users.groups.cancel');
	    Route::put('/groups/checkin', [GroupController::class, 'massCheckIn'])->name('admin.users.groups.massCheckIn');
	    Route::resource('groups', GroupController::class, ['as' => 'admin.users'])->except(['show']);
	    // Roles
	    Route::delete('/roles', [RoleController::class, 'massDestroy'])->name('admin.users.roles.massDestroy');
	    Route::get('/roles/cancel/{role?}', [RoleController::class, 'cancel'])->name('admin.users.roles.cancel');
	    Route::put('/roles/checkin', [RoleController::class, 'massCheckIn'])->name('admin.users.roles.massCheckIn');
	    Route::resource('roles', RoleController::class, ['as' => 'admin.users'])->except(['show']);
	    // Permissions
	    Route::get('/permissions', [PermissionController::class, 'index'])->name('admin.users.permissions.index');
	    Route::patch('/permissions', [PermissionController::class, 'build'])->name('admin.users.permissions.build');
	    Route::put('/permissions', [PermissionController::class, 'rebuild'])->name('admin.users.permissions.rebuild');
	});

	Route::prefix('blog')->group(function () {
	    // Posts
	    Route::delete('/posts', [AdminPostController::class, 'massDestroy'])->name('admin.blog.posts.massDestroy');
	    Route::get('/posts/batch', [AdminPostController::class, 'batch'])->name('admin.blog.posts.batch');
	    Route::put('/posts/batch', [AdminPostController::class, 'massUpdate'])->name('admin.blog.posts.massUpdate');
	    Route::get('/posts/cancel/{post?}', [AdminPostController::class, 'cancel'])->name('admin.blog.posts.cancel');
	    Route::put('/posts/checkin', [AdminPostController::class, 'massCheckIn'])->name('admin.blog.posts.massCheckIn');
	    Route::put('/posts/publish', [AdminPostController::class, 'massPublish'])->name('admin.blog.posts.massPublish');
	    Route::put('/posts/unpublish', [AdminPostController::class, 'massUnpublish'])->name('admin.blog.posts.massUnpublish');
	    Route::get('/posts/{post}/edit/{tab?}', [AdminPostController::class, 'edit'])->name('admin.blog.posts.edit');
	    Route::resource('posts', AdminPostController::class, ['as' => 'admin.blog'])->except(['show', 'edit']);
	    // Categories
	    Route::delete('/categories', [AdminBlogCategoryController::class, 'massDestroy'])->name('admin.blog.categories.massDestroy');
	    Route::get('/categories/cancel/{category?}', [AdminBlogCategoryController::class, 'cancel'])->name('admin.blog.categories.cancel');
	    Route::put('/categories/checkin', [AdminBlogCategoryController::class, 'massCheckIn'])->name('admin.blog.categories.massCheckIn');
	    Route::put('/categories/publish', [AdminBlogCategoryController::class, 'massPublish'])->name('admin.blog.categories.massPublish');
	    Route::put('/categories/unpublish', [AdminBlogCategoryController::class, 'massUnpublish'])->name('admin.blog.categories.massUnpublish');
	    Route::get('/categories/{category}/up', [AdminBlogCategoryController::class, 'up'])->name('admin.blog.categories.up');
	    Route::get('/categories/{category}/down', [AdminBlogCategoryController::class, 'down'])->name('admin.blog.categories.down');
	    Route::get('/categories/{category}/edit/{tab?}', [AdminBlogCategoryController::class, 'edit'])->name('admin.blog.categories.edit');
	    Route::resource('categories', AdminBlogCategoryController::class, ['as' => 'admin.blog'])->except(['show', 'edit']);
	    // Settings
	    Route::get('/settings/{tab?}', [AdminBlogSettingController::class, 'index'])->name('admin.blog.settings.index');
	    Route::patch('/settings', [AdminBlogSettingController::class, 'update'])->name('admin.blog.settings.update');
	});

	Route::prefix('menus')->group(function () {
	    // Menus 
	    Route::delete('/menus', [MenuController::class, 'massDestroy'])->name('admin.menus.menus.massDestroy');
	    Route::get('/menus/cancel/{menu?}', [MenuController::class, 'cancel'])->name('admin.menus.menus.cancel');
	    Route::put('/menus/checkin', [MenuController::class, 'massCheckIn'])->name('admin.menus.menus.massCheckIn');
	    Route::put('/menus/publish', [MenuController::class, 'massPublish'])->name('admin.menus.menus.massPublish');
	    Route::put('/menus/unpublish', [MenuController::class, 'massUnpublish'])->name('admin.menus.menus.massUnpublish');
	    Route::resource('menus', MenuController::class, ['as' => 'admin.menus'])->except(['show']);
	    // Menu Items
	    Route::delete('/{code}/menuitems', [MenuItemController::class, 'massDestroy'])->name('admin.menus.menuitems.massDestroy');
	    Route::get('/{code}/menuitems/cancel/{menuItem?}', [MenuItemController::class, 'cancel'])->name('admin.menus.menuitems.cancel');
	    Route::put('/{code}/menuitems/checkin', [MenuItemController::class, 'massCheckIn'])->name('admin.menus.menuitems.massCheckIn');
	    Route::put('/{code}/menuitems/publish', [MenuItemController::class, 'massPublish'])->name('admin.menus.menuitems.massPublish');
	    Route::put('/{code}/menuitems/unpublish', [MenuItemController::class, 'massUnpublish'])->name('admin.menus.menuitems.massUnpublish');
	    Route::get('/{code}/menuitems/{menuItem}/up', [MenuItemController::class, 'up'])->name('admin.menus.menuitems.up');
	    Route::get('/{code}/menuitems/{menuItem}/down', [MenuItemController::class, 'down'])->name('admin.menus.menuitems.down');
	    Route::get('/{code}/menuitems', [MenuItemController::class, 'index'])->name('admin.menus.menuitems.index');
	    Route::get('/{code}/menuitems/create', [MenuItemController::class, 'create'])->name('admin.menus.menuitems.create');
	    Route::get('/{code}/menuitems/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('admin.menus.menuitems.edit');
	    Route::post('/{code}/menuitems', [MenuItemController::class, 'store'])->name('admin.menus.menuitems.store');
	    Route::put('/{code}/menuitems/{menuItem}', [MenuItemController::class, 'update'])->name('admin.menus.menuitems.update');
	    Route::delete('/{code}/menuitems/{menuItem}', [MenuItemController::class, 'destroy'])->name('admin.menus.menuitems.destroy');
	});

	Route::prefix('settings')->group(function () {
	    Route::get('/general', [GeneralController::class, 'index'])->name('admin.settings.general.index');
	    Route::patch('/general', [GeneralController::class, 'update'])->name('admin.settings.general.update');
	    // Emails
	    Route::delete('/emails', [EmailController::class, 'massDestroy'])->name('admin.settings.emails.massDestroy');
	    Route::get('/emails/cancel/{email?}', [EmailController::class, 'cancel'])->name('admin.settings.emails.cancel');
	    Route::put('/emails/checkin', [EmailController::class, 'massCheckIn'])->name('admin.settings.emails.massCheckIn');
	    Route::resource('emails', EmailController::class, ['as' => 'admin.settings'])->except(['show']);
	});
    });
});


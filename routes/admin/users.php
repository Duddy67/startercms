<?php

use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Users\RoleController;
use App\Http\Controllers\Admin\Users\PermissionController;
use App\Http\Controllers\Admin\Users\GroupController;

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

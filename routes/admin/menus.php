<?php

use App\Http\Controllers\Admin\Menus\MenuController;
use App\Http\Controllers\Admin\Menus\MenuItemController;

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

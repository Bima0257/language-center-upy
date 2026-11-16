<?php

use App\Modules\Identity\Controllers\PermissionController;
use App\Modules\Identity\Controllers\RoleController;
use App\Modules\Identity\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'route.permission', 'role:superadmin'])
    ->prefix('admin/identity')
    ->name('admin.identity.')
    ->group(function (): void {
        Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserRoleController::class, 'create'])->name('users.create');
        Route::post('/users', [UserRoleController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [UserRoleController::class, 'storeBulk'])->name('users.bulk.store');
        Route::put('/users/{pengguna}', [UserRoleController::class, 'update'])->name('users.update');
        Route::delete('/users/{pengguna}', [UserRoleController::class, 'destroy'])->name('users.destroy');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/module', [PermissionController::class, 'showModule'])->name('permissions.module.show');
        Route::post('/permissions/scan', [PermissionController::class, 'scan'])->name('permissions.scan');
        Route::put('/permission-modules', [PermissionController::class, 'updateModule'])->name('permission-modules.update');
        Route::put('/permission-categories/{permissionCategory}', [PermissionController::class, 'updateCategory'])->name('permission-categories.update');
        Route::post('/permission-categories/{permissionCategory}/permissions/{permission}', [PermissionController::class, 'assign'])->name('permission-categories.permissions.assign');
        Route::delete('/permission-categories/permissions/{permission}', [PermissionController::class, 'unassign'])->name('permission-categories.permissions.unassign');
        Route::post('/permissions/{permission}/reset-category', [PermissionController::class, 'reset'])->name('permissions.reset-category');
    });

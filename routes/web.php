<?php

use App\Services\QueueManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', 'auth.login')->name('login');
});

Route::post('/logout', function () {
    $user = Auth::user();

    if ($user !== null) {
        app(QueueManager::class)->releaseAllClaimsFor($user);
    }

    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::livewire('/kiosk', 'kiosk.ticket-issuer')->name('kiosk');
Route::livewire('/display', 'display.queue-board')->name('display');

Route::middleware(['auth', 'can:access-operator'])->group(function (): void {
    Route::livewire('/operator', 'operator.counter-panel')->name('operator.panel');
});

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->group(function (): void {
    Route::livewire('/', 'admin.dashboard')->name('admin.dashboard');
    Route::livewire('/settings', 'admin.settings')->name('admin.settings');
    Route::livewire('/display-tv', 'admin.display-tv')->name('admin.display-tv');
    Route::livewire('/services', 'admin.manage-services')->name('admin.services');
    Route::livewire('/counters', 'admin.manage-counters')->name('admin.counters');
    Route::livewire('/reports', 'admin.queue-reports')->name('admin.reports');
    Route::livewire('/users', 'admin.manage-users')->name('admin.users');

    Route::redirect('/announcement', '/admin/settings?tab=audio');
});

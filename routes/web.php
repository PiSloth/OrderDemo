<?php

use App\Http\Controllers\CustomLogout;
use App\Livewire\CommentHistory;
use App\Livewire\ManufactureCost;
use App\Livewire\Orders\Config;
use App\Livewire\Orders\AddOrder;
use App\Livewire\Orders\BranchReport;
use Illuminate\Support\Facades\Route;
use App\Livewire\Orders\Dashboard;
use App\Livewire\Orders\Help;
use App\Livewire\Orders\Notification;
use App\Livewire\Orders\OrderDashboard;
use App\Livewire\Orders\Orderlists;
use App\Livewire\Orders\PerOrder;
use App\Livewire\Orders\PoolChat;
use App\Livewire\Orders\Report;
use App\Livewire\Orders\Supplier;
use App\Livewire\SupplierDashboard;
use App\Models\Order;
use App\Models\OrderHistory;
use App\View\Components\AppLayout;
use App\View\Components\GuestLayout;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', Report::class);

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';

Route::view('/', 'welcome')->name('welcome');
Route::get('/config', Config::class)
->middleware('can:isSuperAdmin')
->name('config');

Route::middleware(['auth'])->group(function () {
    Route::get('/order/detail/', PerOrder::class)->name('per_order');
    Route::get('/order/branch-report/', BranchReport::class)->name('order-branch-report');
    Route::get('/order/report', Report::class)->name('order-report');
    Route::get('/dologout', [CustomLogout::class, 'doLogout'])->name('doLogout');
    Route::get('/help', Help::class)->name('help');
    Route::get('/order/dashboard', OrderDashboard::class)->name('order-dashboard');
    Route::get('/supplier/dashboard', SupplierDashboard::class)->name('supplier-dashboard');
    Route::get('/manufacture/costing',ManufactureCost::class)->name('manufacture-costing');
    Route::get('/comment/history', CommentHistory::class)->name('comment-history');
});

Route::middleware(['can:isAuthorized'])->group(function () {

    Route::get('/add-order', AddOrder::class)->name('add_order');
    Route::get('/chats', PoolChat::class)->name('chat');
    // Route::get('/order/list', Orderlists::class)->name('ord_list');
    Route::get('/noti', Notification::class)->name('notification');
    Route::get('/addsupplier', Supplier::class)->name('addsupplier');
});

// Route::get('/order/dashboard', Dashboard::class)->name('ord_dashboard')->middleware('auth');
// Route::get('/guest',AppLayout::class);

<?php

use App\Http\Controllers\CustomLogout;
use App\Http\Controllers\Document\EmailListExportController;
use App\Livewire\BranchReport\Dashboard as BranchReportDashboard;
use App\Livewire\BranchReport\SaleAndRepurchase;
use App\Livewire\CommentHistory;
use App\Livewire\ManufactureCost;
use App\Livewire\Document\EmailList as DocumentEmailList;
use App\Livewire\Order\Psi\Branch\StockUpdate;
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
use App\Livewire\Order\Psi\CrateProduct;
use App\Livewire\Order\Psi\CreateProduct;
use App\Livewire\Order\Psi\DailySale;
use App\Livewire\Order\Psi\Focus;
use App\Livewire\Order\Psi\MainBoard;
use App\Livewire\Order\Psi\OrderDetail;
use App\Livewire\Order\Psi\OutOfStockAnalysis;
use App\Livewire\Order\Psi\PhotoShooting;
use App\Livewire\Order\Psi\ProductEdit;
use App\Livewire\Order\Psi\PsiOrderHsitory;
use App\Livewire\Order\Psi\PsiProductSupplier;
use App\Livewire\Order\Psi\Report as PsiReport;
use App\Livewire\Order\Psi\SaleLoss;
use App\Livewire\Order\Psi\StockReceivedByBranch;
use App\Livewire\Orders\OrderHistory as OrdersOrderHistory;
use App\Livewire\Todo\Config as TodoConfig;
use App\Livewire\Todo\TaskComments;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\PsiPrice;
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

Route::middleware(['auth'])->group(function () {
    Route::get('/config', Config::class)
        ->middleware('can:isSuperAdmin')
        ->name('config');
    Route::get('/dologout', [CustomLogout::class, 'doLogout'])->name('doLogout');
    Route::get('/help', Help::class)->name('help');
});

Route::middleware(['auth'])->prefix('document')->name('document.')->group(function () {
    Route::get('email-list', DocumentEmailList::class)->name('email-list');
    Route::get('email-list/export', EmailListExportController::class)->name('email-list.export');
});



Route::middleware(['auth'])->prefix('order')->group(function () {
    Route::get('/order/detail/', PerOrder::class)->name('per_order');
    Route::get('/orders', BranchReport::class)->name('order-branch-report');
    Route::get('/dashboard', OrderDashboard::class)->name('order-dashboard');
    Route::get('/order/dashboard', Report::class)->name('order-report');
    Route::get('/manufacture/costing', ManufactureCost::class)->name('manufacture-costing');
    Route::get('/comment/history', CommentHistory::class)->name('comment-history');
    Route::get('/order/export', OrdersOrderHistory::class)->name('order-export');

    Route::get('/add-order', AddOrder::class)->name('add_order');
    Route::get('/chats', PoolChat::class)->name('chat');
    // Route::get('/order/list', Orderlists::class)->name('ord_list');
    Route::get('/messages', Notification::class)->name('notification');
    Route::get('/addsupplier', Supplier::class)->name('addsupplier');
    // Route::get('/order/dashboard', OrderDashboard::class)->name('order-dashboard');
    // Route::get('/supplier/dashboard', SupplierDashboard::class)->name('supplier-dashboard');
});

Route::middleware(['auth'])->prefix('psi')->group(function () {
    Route::get('/create/product', CreateProduct::class)->name('psi_product');
    Route::get('/mainboard', MainBoard::class)->name('mainboard');
    Route::get('/sale-loss', SaleLoss::class)->name('sale-loss');
    Route::get('/oos', OutOfStockAnalysis::class)->name('oos');
    Route::get('/product/focus', Focus::class)->name('focus');
    Route::get('/product/price', PsiProductSupplier::class)->name('price');
    Route::get('/product/detail/order', OrderDetail::class)->name('order_detail');
    Route::get('/product/shooting', PhotoShooting::class)->name('shooting');
    Route::get('/product/orders', PsiOrderHsitory::class)->name('orders');
    Route::get('/product/daily-sale', DailySale::class)->name('daily_sale');
    Route::get('/psi/oos', OutOfStockAnalysis::class)->name('psi_oos');
    Route::get('/psi/branch/stockupdate', StockUpdate::class)->name('stock-update');
    Route::get('/edit/product', ProductEdit::class)->name('edit_product');
    Route::get('/report', PsiReport::class)->name('psi-report');
});


Route::middleware(['auth'])->prefix('performance')->group(function () {
    Route::get('/branch-score', SaleAndRepurchase::class)->name('sale_repurchase');
    Route::get('/sale-dashboard', BranchReportDashboard::class)->name('report-dashboard');
});


Route::middleware(['auth'])->prefix('todo')->group(function () {
    Route::get('/dashboard', App\Livewire\Todo\Dashboard::class)->name('todo.dashboard');
    Route::get('/config', TodoConfig::class)->name('todo_config');
    Route::get('/list', App\Livewire\Todo\TodoList::class)->name('todo_list');
    Route::get('/comments/{taskId}', TaskComments::class)->name('task_comments');
    Route::get('/notifications', App\Livewire\Todo\Notifications::class)->name('notifications');
});

// API Routes for notifications
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::post('/task-notifications/check', function (Illuminate\Http\Request $request) {
        $lastCheck = $request->input('last_check');
        $userId = auth()->id();

        $newNotifications = \App\Models\TaskNotification::forUser($userId)
            ->where('created_at', '>', $lastCheck)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'created_at' => $notification->created_at->toISOString(),
                ];
            });

        return response()->json([
            'notifications' => $newNotifications,
            'count' => $newNotifications->count()
        ]);
    });
});

// Route::get('/order/dashboard', Dashboard::class)->name('ord_dashboard')->middleware('auth');
// Route::get('/guest',AppLayout::class);

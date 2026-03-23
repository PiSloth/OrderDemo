<?php

use App\Http\Controllers\CustomLogout;
use App\Http\Controllers\Calendar\GoogleCalendarAuthController;
use App\Http\Controllers\Calendar\GoogleCalendarEventsController;
use App\Http\Controllers\Calendar\GoogleSocialiteAuthController;
use App\Http\Controllers\Document\CompanyDocumentImageController;
use App\Http\Controllers\Document\EmailListExportController;
use App\Livewire\BranchReport\Dashboard as BranchReportDashboard;
use App\Livewire\BranchReport\SaleAndRepurchase;
use App\Livewire\CommentHistory;
use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Calendar\AutoSync as CalendarAutoSync;
use App\Livewire\ManufactureCost;
use App\Livewire\Document\EmailList as DocumentEmailList;
use App\Livewire\Document\Library\Browser as DocumentLibraryBrowser;
use App\Livewire\Document\Library\Create as DocumentLibraryCreate;
use App\Livewire\Document\Library\Edit as DocumentLibraryEdit;
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
use App\Livewire\Jewelry\Purchasing\Dashboard as JewelryPurchasingDashboard;
use App\Livewire\Jewelry\Purchasing\Groups\Index as JewelryGroupsIndex;
use App\Livewire\Jewelry\Purchasing\Groups\Show as JewelryGroupsShow;
use App\Http\Controllers\Jewelry\JewelryTemplateController;
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
use App\Livewire\Whiteboard\Board as WhiteboardBoard;
use App\Livewire\Whiteboard\Config as WhiteboardConfig;
use App\Livewire\Whiteboard\Dashboard as WhiteboardDashboard;
use App\Livewire\Whiteboard\Show as WhiteboardShow;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\PsiPrice;
use App\View\Components\AppLayout;
use App\View\Components\GuestLayout;
use Illuminate\Http\Request;


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

    Route::get('library', DocumentLibraryBrowser::class)->name('library.index');
    Route::post('library/upload-image', [CompanyDocumentImageController::class, 'store'])->name('library.upload-image');
    Route::get('library/create', DocumentLibraryCreate::class)->name('library.create');
    Route::get('library/{document}', DocumentLibraryBrowser::class)->name('library.show');
    Route::get('library/{document}/edit', DocumentLibraryEdit::class)->name('library.edit');
});



Route::middleware(['auth'])->prefix('order')->group(function () {
    Route::get('/detail/', PerOrder::class)->name('per_order');
    Route::get('/orders', BranchReport::class)->name('order-branch-report');
    Route::get('/dashboard', OrderDashboard::class)->name('order-dashboard');
    Route::get('/order/dashboard', Report::class)->name('order-report');
    Route::get('/manufacture/costing', ManufactureCost::class)->name('manufacture-costing');
    Route::get('/comment/history', CommentHistory::class)->name('comment-history');
    Route::get('/export', OrdersOrderHistory::class)->name('order-export');

    Route::get('/add-order', AddOrder::class)->name('add_order');
    Route::get('/chats', PoolChat::class)->name('chat');
    Route::get('/order/list', Orderlists::class)->name('ord_list');
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
    // Route::get('/psi/branch/stockupdate', StockUpdate::class)->name('stock-update');
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

Route::middleware(['auth'])->prefix('whiteboard')->name('whiteboard.')->group(function () {
    Route::get('/dashboard', WhiteboardDashboard::class)->name('dashboard');
    Route::get('/board', WhiteboardBoard::class)->name('board');
    Route::get('/config', WhiteboardConfig::class)->name('config');
    Route::get('/{content}', WhiteboardShow::class)->name('show');
});

Route::middleware(['auth'])->prefix('office-asset')->group(function () {
    Route::get('/', App\Livewire\OfficeAssetManager::class)->name('office-asset.index');
});

Route::middleware(['auth'])->prefix('jewelry')->name('jewelry.')->group(function () {
    Route::get('/dashboard', JewelryPurchasingDashboard::class)->name('dashboard');

    Route::get('/template', [JewelryTemplateController::class, 'download'])->name('template');
    Route::get('/template-external-mapping', [JewelryTemplateController::class, 'downloadExternalMappingTemplate'])->name('template_external_mapping');

    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', JewelryGroupsIndex::class)->name('index');
        Route::get('/{group}', JewelryGroupsShow::class)->name('show');
    });
});

Route::middleware(['auth'])->prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', CalendarIndex::class)->name('index');

    Route::get('/auto-sync', CalendarAutoSync::class)->name('auto-sync');

    Route::get('/google-socialite/connect', [GoogleSocialiteAuthController::class, 'connect'])->name('socialite.connect');
    Route::get('/google-socialite/callback', [GoogleSocialiteAuthController::class, 'callback'])->name('socialite.callback');
    Route::post('/google-socialite/disconnect', [GoogleSocialiteAuthController::class, 'disconnect'])->name('socialite.disconnect');

    Route::get('/google/connect', [GoogleCalendarAuthController::class, 'connect'])->name('google.connect');
    Route::get('/google/callback', [GoogleCalendarAuthController::class, 'callback'])->name('google.callback');
    Route::post('/google/disconnect', [GoogleCalendarAuthController::class, 'disconnect'])->name('google.disconnect');

    Route::get('/google/events', [GoogleCalendarEventsController::class, 'index'])->name('google.events');
});

// API Routes for notifications
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::post('/task-notifications/check', function (Request $request) {
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

    Route::post('/calendar-notifications/check', function (Request $request) {
        $lastCheck = $request->input('last_check');
        $userId = auth()->id();

        $newNotifications = \App\Models\CalendarNotification::forUser($userId)
            ->when($lastCheck, fn ($query) => $query->where('created_at', '>', $lastCheck))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'created_at' => $notification->created_at->toISOString(),
                    'data' => $notification->data,
                ];
            });

        return response()->json([
            'notifications' => $newNotifications,
            'count' => $newNotifications->count(),
            'checked_at' => now()->toISOString(),
        ]);
    });
});

// Route::get('/order/dashboard', Dashboard::class)->name('ord_dashboard')->middleware('auth');
// Route::get('/guest',AppLayout::class);

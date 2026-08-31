<?php

use App\Http\Controllers\CustomLogout;
use App\Http\Controllers\Calendar\GoogleCalendarAuthController;
use App\Http\Controllers\Calendar\GoogleCalendarEventsController;
use App\Http\Controllers\Calendar\GoogleSocialiteAuthController;
use App\Http\Controllers\Document\CompanyDocumentImageController;
use App\Http\Controllers\Document\EmailListExportController;
use App\Livewire\Operation\IT\Issue\Configure as ItIssueConfigure;
use App\Livewire\Operation\IT\Issue\Create as ItIssueCreate;
use App\Livewire\Operation\IT\Issue\Dashboard as ItIssueDashboard;
use App\Livewire\Operation\IT\Issue\Index as ItIssueIndex;
use App\Http\Controllers\Operation\IT\ItIssueController;
use App\Livewire\Operation\Branch\BranchConfig;
use App\Livewire\Operation\Branch\BranchChecklist\Operation as BranchChecklistOperation;
use App\Livewire\Operation\Branch\BranchChecklist\Report as BranchChecklistReport;
use App\Http\Controllers\Operation\IT\IssueAssignmentController;
use App\Http\Controllers\Operation\IT\IssueMessageController;
use App\Http\Controllers\Operation\IT\IssueStatusController;
use App\Http\Controllers\Kpi\ImportExportController as KpiImportExportController;
use App\Http\Controllers\Kpi\KpiGroupController;
use App\Http\Controllers\Todo\TodoListController;
use App\Livewire\BranchReport\Dashboard as BranchReportDashboard;
use App\Livewire\BranchReport\SaleAndRepurchase;
use App\Livewire\CommentHistory;
use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Calendar\AutoSync as CalendarAutoSync;
use App\Livewire\ManufactureCost;
use App\Http\Controllers\Document\DocumentLibraryController;
use App\Livewire\Document\EmailList as DocumentEmailList;
use App\Livewire\Kpi\Approvals as KpiApprovals;
use App\Livewire\Kpi\AssociateTasks as KpiAssociateTasks;
use App\Livewire\Kpi\Audit as KpiAudit;
use App\Http\Controllers\Kpi\KpiAssignmentController;
use App\Http\Controllers\Kpi\KpiCertificateController;
use App\Livewire\Kpi\Dashboard as KpiDashboard;
use App\Livewire\Kpi\Exclusions as KpiExclusions;
use App\Livewire\Kpi\Holidays as KpiHolidays;
use App\Livewire\Kpi\ImportExport as KpiImportExport;
use App\Livewire\Kpi\Leaderboard as KpiLeaderboard;
use App\Livewire\Kpi\MyTasks as KpiMyTasks;
use App\Livewire\Kpi\Templates as KpiTemplates;
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
use App\Livewire\Kpi\Manual;
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

Route::get('dashboard', fn() => redirect()->route('report-dashboard'))
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';

Route::get('/', fn() => inertia('Welcome'))->name('welcome');

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

    Route::get('library', [DocumentLibraryController::class, 'index'])->name('library.index');
    Route::post('library', [DocumentLibraryController::class, 'store'])
        ->name('library.store')
        ->middleware('can:document.create');
    Route::post('library/upload-image', [CompanyDocumentImageController::class, 'store'])->name('library.upload-image');
    Route::get('library/suggestions', [DocumentLibraryController::class, 'suggestions'])->name('library.suggestions');
    Route::get('library/search-api', [DocumentLibraryController::class, 'searchApi'])->name('library.search-api');
    Route::get('library/api/{document}', [DocumentLibraryController::class, 'showApi'])->name('library.show-api');
    Route::get('library/create', [DocumentLibraryController::class, 'create'])
        ->name('library.create')
        ->middleware('can:document.create');
    Route::get('library/{document}', [DocumentLibraryController::class, 'show'])->name('library.show');
    Route::get('library/{document}/edit', [DocumentLibraryController::class, 'edit'])
        ->name('library.edit')
        ->middleware('can:document.update');
    Route::put('library/{document}', [DocumentLibraryController::class, 'update'])
        ->name('library.update')
        ->middleware('can:document.update');
    Route::delete('library/{document}', [DocumentLibraryController::class, 'destroy'])
        ->name('library.destroy')
        ->middleware('can:document.delete');
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
    Route::get('/', [TodoListController::class, 'index'])->name('todo.index');
    Route::get('/list', [TodoListController::class, 'index'])->name('todo_list');
    Route::get('/dashboard', [TodoListController::class, 'dashboard'])->name('todo.dashboard');
    Route::post('/tasks', [TodoListController::class, 'store'])->name('todo.tasks.store');
    Route::patch('/tasks/{id}/close', [TodoListController::class, 'closeTask'])->name('todo.tasks.close');
    Route::delete('/tasks/{id}', [TodoListController::class, 'archiveTask'])->name('todo.tasks.archive');
    Route::patch('/tasks/{id}/restore', [TodoListController::class, 'restoreTask'])->name('todo.tasks.restore');
    Route::post('/tasks/{id}/comments', [TodoListController::class, 'storeComment'])->name('todo.tasks.comments');
    Route::post('/task-comments/{commentId}/respond', [TodoListController::class, 'respondActionStep'])->name('todo.comments.respond');
    Route::post('/task-comments/{commentId}/delete', [TodoListController::class, 'destroyComment'])->name('todo.comments.delete_post');
    Route::delete('/task-comments/{commentId}', [TodoListController::class, 'destroyComment'])->name('todo.comments.destroy');

    // Inertia React Config & Category CRUD Routes
    Route::get('/config', [\App\Http\Controllers\Todo\TodoConfigController::class, 'index'])->name('todo_config');
    Route::post('/config/categories', [\App\Http\Controllers\Todo\TodoConfigController::class, 'storeCategory'])->name('todo.config.categories.store');
    Route::patch('/config/categories/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'updateCategory'])->name('todo.config.categories.update');
    Route::delete('/config/categories/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'destroyCategory'])->name('todo.config.categories.destroy');
    Route::post('/config/due-times', [\App\Http\Controllers\Todo\TodoConfigController::class, 'storeDueTime'])->name('todo.config.duetimes.store');
    Route::patch('/config/due-times/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'updateDueTime'])->name('todo.config.duetimes.update');
    Route::delete('/config/due-times/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'destroyDueTime'])->name('todo.config.duetimes.destroy');
    Route::post('/config/statuses', [\App\Http\Controllers\Todo\TodoConfigController::class, 'storeStatus'])->name('todo.config.statuses.store');
    Route::patch('/config/statuses/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'updateStatus'])->name('todo.config.statuses.update');
    Route::delete('/config/statuses/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'destroyStatus'])->name('todo.config.statuses.destroy');
    Route::post('/config/priorities', [\App\Http\Controllers\Todo\TodoConfigController::class, 'storePriority'])->name('todo.config.priorities.store');
    Route::patch('/config/priorities/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'updatePriority'])->name('todo.config.priorities.update');
    Route::delete('/config/priorities/{id}', [\App\Http\Controllers\Todo\TodoConfigController::class, 'destroyPriority'])->name('todo.config.priorities.destroy');

    Route::get('/comments/{taskId}', TaskComments::class)->name('task_comments');
    Route::get('/notifications', App\Livewire\Todo\Notifications::class)->name('notifications');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/sale-kpi', [\App\Http\Controllers\Kpi\KpiSaleKpiController::class, 'index'])->name('kpi.sale-kpi');
    Route::get('/sale-kpi/data', [\App\Http\Controllers\Kpi\KpiSaleKpiController::class, 'getData'])->name('kpi.sale-kpi.data');
    Route::post('/sale-kpi/promote-actions', [\App\Http\Controllers\Kpi\KpiSaleKpiController::class, 'storePromoteAction'])->name('kpi.sale-kpi.promote-actions.store');
    Route::get('/sale-kpi/search-todos', [\App\Http\Controllers\Kpi\KpiSaleKpiController::class, 'searchTodos'])->name('kpi.sale-kpi.search-todos');
    Route::get('/sale-kpi/search-kpi-tasks', [\App\Http\Controllers\Kpi\KpiSaleKpiController::class, 'searchKpiTasks'])->name('kpi.sale-kpi.search-kpi-tasks');
});

Route::middleware(['auth'])->prefix('kpi')->name('kpi.')->group(function () {
    Route::get('/', KpiDashboard::class)->name('dashboard');
    Route::get('/dashboard', KpiDashboard::class)->name('dashboard.home');
    Route::get('/tasks', KpiMyTasks::class)->name('tasks');
    Route::get('/audit', [App\Http\Controllers\Kpi\KpiAuditController::class, 'index'])->name('audit');
    Route::post('/audit/instance/{instance}/status', [App\Http\Controllers\Kpi\KpiAuditController::class, 'updateInstanceStatus'])->name('audit.instance.status');
    Route::post('/audit/step/{stepId}/approve', [App\Http\Controllers\Kpi\KpiAuditController::class, 'approveStep'])->name('audit.step.approve');
    Route::post('/audit/step/{stepId}/reject', [App\Http\Controllers\Kpi\KpiAuditController::class, 'rejectStep'])->name('audit.step.reject');
    Route::post('/audit/exclusion-request', [App\Http\Controllers\Kpi\KpiAuditController::class, 'storeExclusionRequest'])->name('audit.exclusion-request.store');
    Route::post('/audit/exclusion-request/{id}/approve', [App\Http\Controllers\Kpi\KpiAuditController::class, 'approveExclusionRequest'])->name('audit.exclusion-request.approve');
    Route::post('/audit/exclusion-request/{id}/reject', [App\Http\Controllers\Kpi\KpiAuditController::class, 'rejectExclusionRequest'])->name('audit.exclusion-request.reject');
    Route::post('/audit/exclusion-request/{id}/delete', [App\Http\Controllers\Kpi\KpiAuditController::class, 'destroyExclusionRequest'])->name('audit.exclusion-request.delete');
    Route::delete('/audit/exclusion-request/{id}', [App\Http\Controllers\Kpi\KpiAuditController::class, 'destroyExclusionRequest'])->name('audit.exclusion-request.destroy');
    Route::post('/audit/holiday', [App\Http\Controllers\Kpi\KpiAuditController::class, 'storeHoliday'])->name('audit.holiday.store');
    Route::post('/audit/holiday/{id}/delete', [App\Http\Controllers\Kpi\KpiAuditController::class, 'destroyHoliday'])->name('audit.holiday.delete');
    Route::delete('/audit/holiday/{id}', [App\Http\Controllers\Kpi\KpiAuditController::class, 'destroyHoliday'])->name('audit.holiday.destroy');
    Route::get('/certificate', [KpiCertificateController::class, 'index'])->name('certificate');
    Route::get('/exclusions', KpiExclusions::class)->name('exclusions');
    Route::get('/approvals', KpiApprovals::class)->name('approvals');
    Route::get('/associate-tasks', KpiAssociateTasks::class)->name('associate-tasks');
    Route::get('/holidays', KpiHolidays::class)->middleware('can:kpiManageHolidays')->name('holidays');
    Route::get('/templates', [KpiGroupController::class, 'index'])->middleware('can:kpiManageTemplates')->name('templates');
    Route::post('/groups', [KpiGroupController::class, 'storeGroup'])->middleware('can:kpiManageTemplates')->name('groups.store');
    Route::put('/groups/{group}', [KpiGroupController::class, 'updateGroup'])->middleware('can:kpiManageTemplates')->name('groups.update');
    Route::delete('/groups/{group}', [KpiGroupController::class, 'destroyGroup'])->middleware('can:kpiManageTemplates')->name('groups.destroy');
    Route::post('/templates', [KpiGroupController::class, 'storeTemplate'])->middleware('can:kpiManageTemplates')->name('templates.store');
    Route::put('/templates/{template}', [KpiGroupController::class, 'updateTemplate'])->middleware('can:kpiManageTemplates')->name('templates.update');
    Route::delete('/templates/{template}', [KpiGroupController::class, 'destroyTemplate'])->middleware('can:kpiManageTemplates')->name('templates.destroy');
    Route::get('/assignments', [KpiAssignmentController::class, 'index'])->middleware('can:kpiManageAssignments')->name('assignments');
    Route::post('/assignments', [KpiAssignmentController::class, 'store'])->middleware('can:kpiManageAssignments')->name('assignments.store');
    Route::put('/assignments/{assignment}', [KpiAssignmentController::class, 'update'])->middleware('can:kpiManageAssignments')->name('assignments.update');
    Route::delete('/assignments/{assignment}', [KpiAssignmentController::class, 'destroy'])->middleware('can:kpiManageAssignments')->name('assignments.destroy');
    Route::post('/assignments/instances/{instance}', [KpiAssignmentController::class, 'updateInstance'])->middleware('can:isSuperAdmin')->name('assignments.instances.update');
    Route::delete('/assignments/instances/{instance}', [KpiAssignmentController::class, 'destroyInstance'])->middleware('can:isSuperAdmin')->name('assignments.instances.destroy');
    Route::get('/import-export', KpiImportExport::class)->middleware('can:kpiManageImports')->name('import-export');
    Route::get('/import-export/template', [KpiImportExportController::class, 'downloadTemplate'])
        ->middleware('can:kpiManageImports')
        ->name('import-export.template');
    Route::get('/import-export/export', [KpiImportExportController::class, 'exportEmployee'])
        ->middleware('can:kpiManageImports')
        ->name('import-export.employee');
    Route::post('/import-export/import', [KpiImportExportController::class, 'import'])
        ->middleware('can:kpiManageImports')
        ->name('import-export.import');
    Route::get('/import-export/errors/{file}', [KpiImportExportController::class, 'downloadErrorReport'])
        ->middleware('can:kpiManageImports')
        ->name('import-export.errors');
    Route::get('/leaderboard', KpiLeaderboard::class)->name('leaderboard');
    Route::get('/manual', Manual::class)->middleware('can:kpiManageAssignments')->name('manual');
    Route::get('/certificate', [KpiCertificateController::class, 'index'])->name('certificate');
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

Route::middleware(['auth'])->prefix('operations')->name('operation.')->group(function () {
    Route::get('/daily-notes', \App\Livewire\Operation\DailyNotesList::class)->name('daily-notes');
    Route::get('/titles', \App\Livewire\Operation\TitleManager::class)
        // ->middleware('can:manageOperationTitles')
        ->name('titles');
    Route::prefix('branch')->name('branch.')->group(function () {
        Route::get('/checklists', BranchChecklistOperation::class)->name('checklists');
        Route::get('/config', BranchConfig::class)->name('config');
        Route::get('/checklists/config', BranchConfig::class)->name('checklists.config');
        Route::get('/checklists/report', BranchChecklistReport::class)->name('checklists.report');
    });

    Route::prefix('it')->name('it.')->group(function () {
        Route::get('/issues', [ItIssueController::class, 'index'])->name('issues.index');
        Route::get('/issues/create', [ItIssueController::class, 'create'])->name('issues.create');
        Route::post('/issues', [ItIssueController::class, 'store'])->name('issues.store');
        Route::get('/issues/dashboard', [ItIssueController::class, 'dashboard'])->name('issues.dashboard');
        Route::get('/issues/reports', [ItIssueController::class, 'reports'])->name('issues.reports');
        Route::get('/issues/reports/export', [ItIssueController::class, 'exportReport'])->name('issues.reports.export');
        Route::get('/issues/configure', [ItIssueController::class, 'configure'])->name('issues.configure');

        // IT Issue Configuration CRUD & Swap Routes
        Route::post('/issues/configure/priorities', [ItIssueController::class, 'storePriorityConfig'])->name('issues.configure.priorities.store');
        Route::patch('/issues/configure/priorities/{id}', [ItIssueController::class, 'updatePriorityConfig'])->name('issues.configure.priorities.update');
        Route::delete('/issues/configure/priorities/{id}', [ItIssueController::class, 'destroyPriorityConfig'])->name('issues.configure.priorities.destroy');
        Route::post('/issues/configure/priorities/swap', [ItIssueController::class, 'swapPriorityConfig'])->name('issues.configure.priorities.swap');

        Route::post('/issues/configure/importance', [ItIssueController::class, 'storeImportanceConfig'])->name('issues.configure.importance.store');
        Route::patch('/issues/configure/importance/{id}', [ItIssueController::class, 'updateImportanceConfig'])->name('issues.configure.importance.update');
        Route::delete('/issues/configure/importance/{id}', [ItIssueController::class, 'destroyImportanceConfig'])->name('issues.configure.importance.destroy');
        Route::post('/issues/configure/importance/swap', [ItIssueController::class, 'swapImportanceConfig'])->name('issues.configure.importance.swap');

        Route::post('/issues/configure/statuses', [ItIssueController::class, 'storeStatusConfig'])->name('issues.configure.statuses.store');
        Route::patch('/issues/configure/statuses/{id}', [ItIssueController::class, 'updateStatusConfig'])->name('issues.configure.statuses.update');
        Route::delete('/issues/configure/statuses/{id}', [ItIssueController::class, 'destroyStatusConfig'])->name('issues.configure.statuses.destroy');
        Route::post('/issues/configure/statuses/swap', [ItIssueController::class, 'swapStatusConfig'])->name('issues.configure.statuses.swap');

        Route::post('/issues/configure/root-causes', [ItIssueController::class, 'storeRootCauseConfig'])->name('issues.configure.root-causes.store');
        Route::patch('/issues/configure/root-causes/{id}', [ItIssueController::class, 'updateRootCauseConfig'])->name('issues.configure.root-causes.update');
        Route::delete('/issues/configure/root-causes/{id}', [ItIssueController::class, 'destroyRootCauseConfig'])->name('issues.configure.root-causes.destroy');
        Route::post('/issues/configure/root-causes/swap', [ItIssueController::class, 'swapRootCauseConfig'])->name('issues.configure.root-causes.swap');
        Route::put('/issues/{issue}', [ItIssueController::class, 'update'])->name('issues.update');
        Route::delete('/issues/{issue}', [ItIssueController::class, 'destroy'])->name('issues.destroy');
        Route::post('/issues/{id}/restore', [ItIssueController::class, 'restore'])->name('issues.restore');
        Route::delete('/issues/{id}/force-delete', [ItIssueController::class, 'forceDelete'])->name('issues.force-delete');
        Route::post('/issues/reorder-sequence', [ItIssueController::class, 'reorderSequence'])->name('issues.reorder-sequence');
        Route::patch('/issues/{issue}/priority', [ItIssueController::class, 'updatePriority'])->name('issues.priority.update');
        Route::patch('/issues/{issue}/status', [ItIssueController::class, 'updateStatus'])->name('issues.status.update');
        Route::patch('/issues/{issue}/reported-date', [ItIssueController::class, 'updateReportedDate'])->name('issues.reported-date.update');
        Route::patch('/issues/{issue}/due-date', [ItIssueController::class, 'updateDueDate'])->name('issues.due-date.update');
        Route::post('/issues/{issue}/override-sla', [ItIssueController::class, 'overrideSla'])->name('issues.override-sla');
        Route::patch('/issues/{issue}/assignment', [IssueAssignmentController::class, 'update'])->name('issues.assignment.update');
        Route::post('/issues/{issue}/messages', [ItIssueController::class, 'addMessage'])->name('issues.messages.store');
    });
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
            ->when($lastCheck, fn($query) => $query->where('created_at', '>', $lastCheck))
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

Route::middleware(['auth'])->prefix('taxonomies')->name('taxonomies.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TaxonomyController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\TaxonomyController::class, 'store'])->name('store');
    Route::post('/rename-group', [\App\Http\Controllers\TaxonomyController::class, 'renameGroup'])->name('rename-group');
    Route::delete('/groups/{groupKey}', [\App\Http\Controllers\TaxonomyController::class, 'destroyGroup'])->name('destroy-group');
    Route::put('/{taxonomy}', [\App\Http\Controllers\TaxonomyController::class, 'update'])->name('update');
    Route::delete('/{taxonomy}', [\App\Http\Controllers\TaxonomyController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/create', [\App\Http\Controllers\ReportController::class, 'create'])->name('create');
    Route::get('/analytic-board', [\App\Http\Controllers\ReportController::class, 'analyticBoard'])->name('analytic-board');
    Route::get('/history-blocks', [\App\Http\Controllers\ReportController::class, 'history'])->name('history-blocks');
    Route::get('/imageboard-threads', [\App\Http\Controllers\ReportController::class, 'imageboardThreads'])->name('imageboard-threads');
    Route::post('/upload-image', [\App\Http\Controllers\ReportController::class, 'uploadImage'])->name('upload-image');
    Route::get('/{report}/edit', [\App\Http\Controllers\ReportController::class, 'edit'])->name('edit');
    Route::post('/save', [\App\Http\Controllers\ReportController::class, 'store'])->name('store');
    Route::post('/{report}/replies', [\App\Http\Controllers\ReportController::class, 'reply'])->name('replies');
    Route::post('/{report}/metadata', [\App\Http\Controllers\ReportController::class, 'updateMetadata'])->name('metadata');
    Route::put('/{report}', [\App\Http\Controllers\ReportController::class, 'update'])->name('update');
});

Route::middleware(['auth'])->prefix('training')->name('training.')->group(function () {
    // Compliance & Analytics Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Training\TrainingComplianceController::class, 'index'])->name('dashboard');

    // Office Positions
    Route::get('/office-positions', [\App\Http\Controllers\Training\OfficePositionController::class, 'index'])->name('office-positions.index');
    Route::post('/office-positions', [\App\Http\Controllers\Training\OfficePositionController::class, 'store'])->name('office-positions.store');
    Route::put('/office-positions/{officePosition}', [\App\Http\Controllers\Training\OfficePositionController::class, 'update'])->name('office-positions.update');
    Route::delete('/office-positions/{officePosition}', [\App\Http\Controllers\Training\OfficePositionController::class, 'destroy'])->name('office-positions.destroy');
    Route::post('/office-positions/{officePosition}/assign-users', [\App\Http\Controllers\Training\OfficePositionController::class, 'assignUsers'])->name('office-positions.assign-users');

    // Training Catalog & Scopes
    Route::get('/trainings', [\App\Http\Controllers\Training\TrainingController::class, 'index'])
        ->middleware('can:training.catalog.view')
        ->name('trainings.index');

    Route::post('/trainings', [\App\Http\Controllers\Training\TrainingController::class, 'store'])
        ->middleware('can:training.catalog.create')
        ->name('trainings.store');

    Route::put('/trainings/{training}', [\App\Http\Controllers\Training\TrainingController::class, 'update'])
        ->middleware('can:training.catalog.update')
        ->name('trainings.update');

    Route::delete('/trainings/{training}', [\App\Http\Controllers\Training\TrainingController::class, 'destroy'])
        ->middleware('can:training.catalog.delete')
        ->name('trainings.destroy');

    Route::post('/trainings/{training}/assign', [\App\Http\Controllers\Training\TrainingController::class, 'triggerAssign'])
        ->middleware('can:training.catalog.update')
        ->name('trainings.assign');

    // Sessions & Attendance
    Route::get('/sessions', [\App\Http\Controllers\Training\TrainingSessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions', [\App\Http\Controllers\Training\TrainingSessionController::class, 'store'])->name('sessions.store');
    Route::put('/sessions/{session}', [\App\Http\Controllers\Training\TrainingSessionController::class, 'update'])->name('sessions.update');
    Route::put('/sessions/{session}/status', [\App\Http\Controllers\Training\TrainingSessionController::class, 'updateStatus'])->name('sessions.update-status');
    Route::put('/sessions/{session}/attendance', [\App\Http\Controllers\Training\TrainingSessionController::class, 'updateAttendance'])->name('sessions.update-attendance');

    // Tests & Question Builder
    Route::get('/trainings/{training}/test-builder', [\App\Http\Controllers\Training\TestController::class, 'builder'])->name('tests.builder');
    Route::put('/tests/{test}/save-builder', [\App\Http\Controllers\Training\TestController::class, 'saveBuilder'])->name('tests.save-builder');

    // Employee Portal
    Route::get('/my-trainings', [\App\Http\Controllers\Training\TrainingEmployeeController::class, 'myTrainings'])->name('employee.my-trainings');
    Route::get('/assignments/{assignment}/tests/{test}/take', [\App\Http\Controllers\Training\TrainingEmployeeController::class, 'takeTest'])->name('employee.take-test');
    Route::post('/assignments/{assignment}/tests/{test}/submit', [\App\Http\Controllers\Training\TrainingEmployeeController::class, 'submitTest'])->name('employee.submit-test');

    // Printable Training Scorecard & Certificate Template
    Route::get('/scorecard', [\App\Http\Controllers\Training\TrainingScorecardController::class, 'show'])->name('scorecard');
    Route::get('/assignments/{assignment}/scorecard', [\App\Http\Controllers\Training\TrainingScorecardController::class, 'show'])->name('employee.scorecard');
    Route::get('/assignments/{assignment}/attempt-history', [\App\Http\Controllers\Training\TrainingComplianceController::class, 'attemptHistory'])->name('assignments.attempt-history');
});

// Route::get('/order/dashboard', Dashboard::class)->name('ord_dashboard')->middleware('auth');
// Route::get('/guest',AppLayout::class);


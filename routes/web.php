<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;


use App\Http\Controllers\PermissionGroup;
use App\Http\Controllers\PermissionModule;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuRoleController;
use App\Http\Controllers\CompanyDetails;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PassbookController;
use App\Http\Controllers\CompanyDetailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HistoricalReportsController;
use App\Http\Controllers\InvoicecronController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ReportActionsController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CustomerPaymentsController;
use App\Http\Controllers\SupplierPaymentsController;
use App\Http\Controllers\OnAccountPaymentController;
use App\Http\Controllers\WashDatabaseController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\SupplierPaymentHistoryController;
use App\Http\Controllers\CustomerReturnController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\DumpController;
use App\Http\Controllers\StockClosingController;
use App\Http\Controllers\StockCheckController;
use App\Http\Controllers\CustomerHistoryController;
use App\Http\Controllers\SupplierHistoryController;
use App\Http\Controllers\ProductHistoryController;
use App\Http\Controllers\ProfitLossController;

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
 
Route::get('/logout', function () {
    return view('logout'); 
	//return response('<p style="text-align:center;margin-top:50px;font-size:20px;">Please logout from panel instead of direct logout from url. or <br/> </p>', 200);
});

// test cases.
Route::get('/testing', [TestController::class, 'index'])->name('testing');
Route::get('/email-testing', [\App\Http\Controllers\EmailsController::class, 'test'])->name('email-testing');

Route::get('/wash', [WashDatabaseController::class, 'showTables'])->name('wash');
Route::post('/wash-truncate', [WashDatabaseController::class, 'truncateTables'])->name('wash-truncate');

require_once('common.php');

Route::get('/fix-payments', function () {
    $payments = \DB::table('payments')->get();
    $customerPayments = \DB::table('customer_payments')
        ->leftJoin('payments', 'payments.id', '=', 'customer_payments.payment_id')
        ->select('customer_payments.id','customer_payments.customer_invoice_id','customer_payments.payment_id','customer_payments.amount','customer_payments.initiated','payments.type as payment_type', 'payments.id as payments_table_id')
        ->orderBy('customer_payments.id','desc')
        ->limit(20)
        ->get();
    $paymentId2 = \DB::table('payments')->where('id', 2)->first();
    $columnsList = \DB::select("SHOW COLUMNS FROM payments");
    return response()->json([
        'payments_table' => $payments,
        'payment_id_2_row' => $paymentId2,
        'payments_columns' => $columnsList,
        'customer_payments' => $customerPayments,
    ]);
});

Route::get('/seed-payments', function () {
    $methods = [
        ['id' => 2, 'type' => 'Cash'],
        ['id' => 3, 'type' => 'Cheque'],
        ['id' => 4, 'type' => 'Card'],
        ['id' => 5, 'type' => 'Bank Transfer'],
    ];
    foreach ($methods as $m) {
        $exists = \DB::table('payments')->where('id', $m['id'])->exists();
        if (!$exists) {
            \DB::table('payments')->insert(['id' => $m['id'], 'type' => $m['type'], 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    return response()->json(['status' => 'done', 'rows' => \DB::table('payments')->get()]);
});

Route::get('/debug-os', function () {
    $db = \DB::connection('mysql');

    $customerReturns = $db->table('stock_products as sp')
        ->leftJoin('products as p', 'p.id', '=', 'sp.product_id')
        ->leftJoin('customers as c', 'c.id', '=', 'sp.customer_id')
        ->select('sp.id','p.name as product','c.name as customer','sp.stock','sp.price','sp.invoice_id',\DB::raw("DATE(sp.created_at) as date"))
        ->where('sp.event','customer_return')->where('sp.is_archived',0)
        ->orderBy('sp.created_at')->get();

    $supplierReturns = $db->table('stock_products as sp')
        ->leftJoin('products as p', 'p.id', '=', 'sp.product_id')
        ->leftJoin('suppliers as s', 's.id', '=', 'sp.supplier_id')
        ->select('sp.id','p.name as product','s.name as supplier','sp.stock','sp.price','sp.invoice_id',\DB::raw("DATE(sp.created_at) as date"))
        ->where('sp.event','supplier_return')->where('sp.is_archived',0)
        ->orderBy('sp.created_at')->get();

    $dumps = $db->table('stock_products as sp')
        ->leftJoin('products as p', 'p.id', '=', 'sp.product_id')
        ->leftJoin('suppliers as s', 's.id', '=', 'sp.supplier_id')
        ->select('sp.id','p.name as product','s.name as supplier','sp.stock','sp.price','sp.invoice_id',\DB::raw("DATE(sp.created_at) as date"))
        ->where('sp.event','dump')->where('sp.is_archived',0)
        ->orderBy('sp.created_at')->get();

    $stockCheck = $db->table('stock_products as sp')
        ->leftJoin('products as p', 'p.id', '=', 'sp.product_id')
        ->select('sp.id','p.name as product','sp.event','sp.type','sp.stock','sp.price','sp.supplier_id','sp.customer_id','sp.invoice_id',\DB::raw("DATE(sp.created_at) as date"))
        ->where('sp.is_archived',0)
        ->orderBy('sp.created_at')->get();

    $unassignedSup = $db->table('supplier_invoice_products as sip')
        ->leftJoin('products as p', 'p.id', '=', 'sip.product_id')
        ->leftJoin('suppliers as s', 's.id', '=', 'sip.supplier_id')
        ->select('sip.id','p.name as product','s.name as supplier','sip.quantity','sip.unit_price','sip.sub_total',\DB::raw("DATE(sip.created_at) as date"))
        ->where('sip.is_archive',0)
        ->orderBy('sip.created_at')->get();

    $stockClosings = $db->table('stock_closings as sc')
        ->leftJoin('products as p', 'p.id', '=', 'sc.product_id')
        ->select('sc.id','p.name as product','sc.stock as closing_stock','sc.remark','sc.is_reviewed',\DB::raw("DATE(sc.created_at) as date"))
        ->orderBy('sc.created_at')->get();

    return response()->json([
        'customer_returns' => $customerReturns,
        'supplier_returns' => $supplierReturns,
        'dumps' => $dumps,
        'stock_check_all' => $stockCheck,
        'unassigned_supplier' => $unassignedSup,
        'stock_closings' => $stockClosings,
        'summary' => [
            'customer_returns' => count($customerReturns),
            'supplier_returns' => count($supplierReturns),
            'dumps' => count($dumps),
            'stock_check_all' => count($stockCheck),
            'unassigned_supplier' => count($unassignedSup),
            'stock_closings' => count($stockClosings),
        ]
    ]);
});

Route::middleware(['localization', 'domains'])->group(function () {
    Route::get('/', function () {
        return view('auth.login');
    });
    Route::get('/invoicedeletecron', [InvoicecronController::class, 'invoicedeletecron'])->name('invoicedeletecron');

    Auth::routes();

    // ── Superadmin panel — separate /admin/login, role-gated (superadmin only) ──
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login'])->name('login.post');
        Route::post('logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'logout'])->name('logout');

        Route::middleware('superadmin')->group(function () {
            Route::get('/', fn () => redirect()->route('admin.dashboard'));

            // Dashboard — main overview (first screen after login)
            Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

            // Vendors — CRUD of vendor accounts (name, status, subscription action)
            Route::get('vendors', [\App\Http\Controllers\Admin\VendorController::class, 'index'])->name('vendors.index');
            Route::get('vendors/create', [\App\Http\Controllers\Admin\VendorController::class, 'create'])->name('vendors.create');
            Route::post('vendors', [\App\Http\Controllers\Admin\VendorController::class, 'store'])->name('vendors.store');
            Route::get('vendors/{id}', [\App\Http\Controllers\Admin\VendorController::class, 'show'])->name('vendors.show');
            Route::get('vendors/{id}/edit', [\App\Http\Controllers\Admin\VendorController::class, 'edit'])->name('vendors.edit');
            Route::put('vendors/{id}', [\App\Http\Controllers\Admin\VendorController::class, 'update'])->name('vendors.update');
            Route::post('vendors/{id}/toggle', [\App\Http\Controllers\Admin\VendorController::class, 'toggleStatus'])->name('vendors.toggle');
            Route::delete('vendors/{id}', [\App\Http\Controllers\Admin\VendorController::class, 'destroy'])->name('vendors.destroy');

            // Domains — subdomain / custom-domain management (sites table)
            Route::get('domains', [\App\Http\Controllers\Admin\DomainController::class, 'index'])->name('domains.index');
            Route::get('domains/create', [\App\Http\Controllers\Admin\DomainController::class, 'create'])->name('domains.create');
            Route::post('domains', [\App\Http\Controllers\Admin\DomainController::class, 'store'])->name('domains.store');
            Route::get('domains/{id}/edit', [\App\Http\Controllers\Admin\DomainController::class, 'edit'])->name('domains.edit');
            Route::put('domains/{id}', [\App\Http\Controllers\Admin\DomainController::class, 'update'])->name('domains.update');
            Route::post('domains/{id}/toggle', [\App\Http\Controllers\Admin\DomainController::class, 'toggleStatus'])->name('domains.toggle');
            Route::post('domains/{id}/verify', [\App\Http\Controllers\Admin\DomainController::class, 'verify'])->name('domains.verify');
            Route::delete('domains/{id}', [\App\Http\Controllers\Admin\DomainController::class, 'destroy'])->name('domains.destroy');

            // Plans — subscription plan tiers (price, limits, feature toggles)
            Route::get('plans', [\App\Http\Controllers\Admin\PlansController::class, 'index'])->name('plans.index');
            Route::get('plans/create', [\App\Http\Controllers\Admin\PlansController::class, 'create'])->name('plans.create');
            Route::post('plans', [\App\Http\Controllers\Admin\PlansController::class, 'store'])->name('plans.store');
            Route::get('plans/{id}/edit', [\App\Http\Controllers\Admin\PlansController::class, 'edit'])->name('plans.edit');
            Route::put('plans/{id}', [\App\Http\Controllers\Admin\PlansController::class, 'update'])->name('plans.update');
            Route::post('plans/{id}/toggle', [\App\Http\Controllers\Admin\PlansController::class, 'toggleStatus'])->name('plans.toggle');
            Route::delete('plans/{id}', [\App\Http\Controllers\Admin\PlansController::class, 'destroy'])->name('plans.destroy');

            // Billing — revenue, invoices, subscription payments
            Route::get('billing', [\App\Http\Controllers\Admin\BillingController::class, 'index'])->name('billing.index');

            // Analytics — platform KPIs + trend charts
            Route::get('analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');

            // Audit Logs — filterable platform/business activity
            Route::get('audit', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit.index');

            // Admin Users — platform admin accounts + roles/permissions matrix
            Route::get('admin-users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('adminusers.index');
            Route::get('admin-users/create', [\App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('adminusers.create');
            Route::post('admin-users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('adminusers.store');
            Route::post('admin-users/matrix', [\App\Http\Controllers\Admin\AdminUserController::class, 'updateMatrix'])->name('adminusers.matrix');
            Route::get('admin-users/{id}/edit', [\App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('adminusers.edit');
            Route::put('admin-users/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('adminusers.update');
            Route::delete('admin-users/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('adminusers.destroy');

            // Subscription — listing + history (per vendor)
            Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::post('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'store'])->name('subscriptions.store');
            Route::delete('subscriptions/{id}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');


            // Notifications — platform announcements (audience-targeted)
            Route::get('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('notifications.store');
            Route::delete('notifications/{id}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');

            // Settings — platform branding / SMTP / payments / security
            Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');

            // Superadmin's own account
            Route::get('account', [\App\Http\Controllers\Admin\AccountController::class, 'edit'])->name('account.edit');
            Route::post('account', [\App\Http\Controllers\Admin\AccountController::class, 'update'])->name('account.update');
            Route::post('account/password', [\App\Http\Controllers\Admin\AccountController::class, 'updatePassword'])->name('account.password');
        });
    });

    Route::group(['prefix' => 'company_details', 'as' => 'company_details.'], function(){
        Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
            Route::get('/index', [CompanyDetailController::class, 'index'])->name('index');
        });
        Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
            Route::get('/create', [CompanyDetailController::class, 'create'])->name('create');
            Route::post('store', [CompanyDetailController::class, 'store'])->name('store');
        });
        Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
            Route::get('/index', [CompanyDetailController::class, 'edit'])->name('index');
            Route::post('/update', [CompanyDetailController::class, 'update'])->name('update');
        });
        // Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
        //     Route::post('/purchases-list', [PurchaseController::class, 'ajaxDailyBookPurchase'])->name('purchases-list');
        // });
});
    Route::get('/noPermission', [HomeController::class, 'noPermission'])->name('noPermission');
	Route::get('/workTime', [HomeController::class, 'workTime'])->name('workTime');
    Route::middleware(['auth','rbac','wtpermission'])->group(function () {

        Route::group(['prefix' => 'management', 'as' => 'management.'], function(){

            Route::group(['prefix' => 'roles', 'as' => 'roles.'], function(){
                Route::group(['prefix' => 'modules', 'as' => 'modules.'], function(){
                    Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                        Route::get('index', [PermissionModule::class, 'index'])->name('index');
                    });

                    Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                        Route::get('/create', [PermissionModule::class, 'create'])->name('create');
                        Route::post('store', [PermissionModule::class, 'store'])->name('store');
                    });

                    Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                        Route::get('/{permissionModule}/edit', [PermissionModule::class, 'edit'])->name('edit');
                        Route::put('/{permissionModule}', [PermissionModule::class, 'update'])->name('update');
                    });

                    Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                        Route::delete('/{permissionModule}', [PermissionModule::class, 'destroy'])->name('destroy');
                    });
                });

                Route::group(['prefix' => 'groups', 'as' => 'groups.'], function(){
                    Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                        Route::get('index', [PermissionGroup::class, 'index'])->name('index');
                    });

                    Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                        Route::get('create', [PermissionGroup::class, 'create'])->name('create');
                        Route::post('store', [PermissionGroup::class, 'store'])->name('store');
                    });

                    Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                        Route::get('/{permissionGroup}/edit', [PermissionGroup::class, 'edit'])->name('edit');
                        Route::put('/{permissionGroup}', [PermissionGroup::class, 'update'])->name('update');
                    });

                    Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                        Route::delete('/{permissionGroup}', [PermissionGroup::class, 'destroy'])->name('destroy');
                    });
                });

                Route::group(['prefix' => 'role', 'as' => 'role.'], function(){
                    Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                        Route::get('index', [RoleController::class, 'index'])->name('index');
						Route::get('list', [RoleController::class, 'list'])->name('list');
                    });

                    Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                        Route::get('/create', [RoleController::class, 'create'])->name('create');
                        Route::post('store', [RoleController::class, 'store'])->name('store');
                    });

                    Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
                        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
                    });

                    Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
                    });
                });

                Route::group(['prefix' => 'permission', 'as' => 'permission.'], function(){
                    Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                        Route::get('index', [PermissionRoleController::class, 'index'])->name('index');
                        Route::get('list', [PermissionRoleController::class, 'list'])->name('list');
                    });

                    Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                        //Route::get('permissionRole/create', [PermissionRoleController::class, 'create'])->name('permissionRole.create');
                        Route::post('store', [PermissionRoleController::class, 'store'])->name('store');
                    });

                    Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                        Route::get('/{permissionRole}/edit', [PermissionRoleController::class, 'edit'])->name('edit');
                        //Route::put('permissionRole/{permissionRole}', [PermissionRoleController::class, 'update'])->name('permissionRole.update');
                    });

                    Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                        Route::delete('/{permissionRole}', [PermissionRoleController::class, 'destroy'])->name('destroy');
                    });
                });
            });

            Route::group(['prefix' => 'users', 'as' => 'users.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('index', [UserController::class, 'index'])->name('index');
                    Route::get('list', [UserController::class, 'list'])->name('list');
                });

                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    Route::get('/create', [UserController::class, 'create'])->name('create');
                    Route::post('store', [UserController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
                    Route::put('/{user}', [UserController::class, 'update'])->name('update');
                });

                Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
                });
            });

            // Settings page (combined Roles, Permissions, Users, Account, Company)
            Route::get('settings', function() {
                $adminData   = \Auth::user();
                $companyData = \App\Models\CompanyDetailModel::first();
                $supplierSetting = \App\Models\GeneralSetting::firstOrCreate(
                    ['setting' => 'show_suppliers'],
                    ['status' => 1, 'is_archive' => 0, 'user_id' => \Auth::id()]
                );
                $showSuppliers = (int) $supplierSetting->status;
                // Live row counts for the Delete Data cards — each count is the "primary" table
                // of that section (the same tables the data-delete route clears).
                $deleteCounts = [
                    'sales'     => (int) \DB::table('customer_invoices')->count(),
                    'purchases' => (int) \DB::table('supplier_invoices')->count(),
                    'products'  => (int) \DB::table('products')->count(),
                    'customers' => (int) \DB::table('customers')->count(),
                    'suppliers' => (int) \DB::table('suppliers')->count(),
                ];
                return view('settings.index', compact('adminData', 'companyData', 'showSuppliers', 'deleteCounts'));
            })->name('settings.index');
            Route::post('settings/password/update', [\App\Http\Controllers\AdminController::class, 'updatePassword'])->name('settings.password.update');
            Route::post('settings/data-delete', function(\Illuminate\Http\Request $request) {
                $section = $request->input('section');

                // ── Unlimited execution: this route can never time out, regardless of row count ──
                // PHP-level: drop the time/memory ceilings just for this request.
                @set_time_limit(0);
                @ini_set('max_execution_time', '0');
                @ini_set('memory_limit', '-1');
                ignore_user_abort(true);

                // Note: We only DELETE rows, never DROP tables. Reference tables
                // (payments definitions, customers, suppliers, products) are NOT touched here.
                // Hidden buttons (customers/suppliers/products/stock/payments) kept in $map
                // for future use — only sales & purchases are exposed in the UI right now.
                $map = [
                    'sales' => [
                        // Stock entries created by sales (consumption + customer returns)
                        ['table' => 'stock_products', 'where' => function($q){
                            $q->whereIn('event', ['stock_consumed','customer_return'])->where('type', 'customer');
                        }],
                        // Sales-side payments
                        ['table' => 'customer_payments'],
                        ['table' => 'invoice_payments'],
                        // Sales invoice rows
                        ['table' => 'customer_invoice_products'],
                        ['table' => 'customer_invoice_orders'],
                        ['table' => 'customer_invoices'],
                    ],
                    'purchases' => [
                        // Stock entries created by purchases (added + supplier returns + dumps)
                        ['table' => 'stock_products', 'where' => function($q){
                            $q->whereIn('event', ['stock_added','stock_updated','supplier_return','dump'])->where('type', 'supplier');
                        }],
                        // Purchase-side payments
                        ['table' => 'supplier_payments'],
                        // Purchase invoice rows
                        ['table' => 'supplier_invoice_products'],
                        ['table' => 'supplier_invoice_orders'],
                        ['table' => 'supplier_invoices'],
                    ],
                    'customers' => [['table' => 'customers']],
                    'suppliers' => [['table' => 'suppliers']],
                    'products'  => [['table' => 'products']],
                    'stock'     => [['table' => 'stock_closings'], ['table' => 'stock_products']],
                    'payments'  => [['table' => 'customer_payments'], ['table' => 'invoice_payments']],
                ];
                if (!isset($map[$section])) {
                    return response()->json(['success'=>false,'message'=>'Invalid section.'], 422);
                }

                // ── Bulletproof delete strategy — works on tables of ANY size ──
                // Strategy:
                //   1. SELECT primary-key ids in small batches matching the filter (fast — PK scan)
                //   2. DELETE WHERE id IN (...) — fastest InnoDB path, locks only those exact rows
                //   3. Infinite retry on lock_wait_timeout (1205) and deadlock (1213) errors with
                //      exponential backoff — we WAIT it out instead of failing the request.
                //   4. No hard batch cap — keep going till the filter returns 0 rows.
                $BATCH_SIZE   = 300; // small enough to lock briefly even on contended tables

                // Maximum patience at the DB layer too — InnoDB defaults to 50s; we set 1 hour per wait.
                // If contention is so severe even this isn't enough, the retry loop catches it anyway.
                try { \DB::statement('SET SESSION innodb_lock_wait_timeout = 3600'); } catch (\Throwable $e) {}
                try { \DB::statement('SET SESSION transaction_isolation = "READ-COMMITTED"'); } catch (\Throwable $e) {}
                // MySQL server-side connection timeouts also bumped to "essentially never" for this session.
                try { \DB::statement('SET SESSION wait_timeout = 28800'); } catch (\Throwable $e) {}        // 8h
                try { \DB::statement('SET SESSION interactive_timeout = 28800'); } catch (\Throwable $e) {} // 8h
                try { \DB::statement('SET SESSION net_read_timeout = 600'); } catch (\Throwable $e) {}
                try { \DB::statement('SET SESSION net_write_timeout = 600'); } catch (\Throwable $e) {}

                \DB::statement('SET FOREIGN_KEY_CHECKS=0');

                $deletedCounts = [];

                try {
                    foreach ($map[$section] as $entry) {
                        $table = $entry['table'];
                        $hasFilter = isset($entry['where']) && is_callable($entry['where']);

                        // Build a SELECT query (NOT a DELETE) that picks PRIMARY KEY ids matching the filter.
                        $buildSelect = function() use ($table, $entry, $hasFilter) {
                            $q = \DB::table($table)->select('id');
                            if ($hasFilter) $entry['where']($q);
                            return $q;
                        };

                        $totalDeleted = 0;
                        $consecutiveEmpty = 0;

                        while (true) {
                            // Read the next page of ids
                            $ids = $buildSelect()->limit($BATCH_SIZE)->pluck('id')->all();
                            if (empty($ids)) {
                                // Double-check: see if the table genuinely has nothing left for this filter.
                                // Two consecutive empty reads = done.
                                if (++$consecutiveEmpty >= 2) break;
                                usleep(100000); // 100ms breath, then re-check
                                continue;
                            }
                            $consecutiveEmpty = 0;

                            // Delete by primary key with INFINITE retry on lock_wait_timeout (1205) or deadlock (1213).
                            // Backoff caps at ~5s per wait so we don't spin tight, but loop never gives up.
                            $attempt = 0;
                            $deletedThisBatch = 0;
                            while (true) {
                                try {
                                    $deletedThisBatch = \DB::table($table)->whereIn('id', $ids)->delete();
                                    break; // success
                                } catch (\Illuminate\Database\QueryException $qe) {
                                    $code = (string) ($qe->errorInfo[1] ?? '');
                                    $msg  = $qe->getMessage();
                                    $isLockTimeout = ($code === '1205') || stripos($msg, 'lock wait timeout') !== false;
                                    $isDeadlock    = ($code === '1213') || stripos($msg, 'deadlock') !== false;
                                    if ($isLockTimeout || $isDeadlock) {
                                        // Exponential backoff: 200ms, 400ms, 800ms, 1.6s, 3.2s, 5s (cap)
                                        $waitMs = min(5000000, 200000 * (1 << min($attempt, 5)));
                                        usleep($waitMs);
                                        $attempt++;
                                        continue;
                                    }
                                    // Non-lock error → don't infinite-retry; bubble up so the operator sees it
                                    throw $qe;
                                }
                            }
                            $totalDeleted += $deletedThisBatch;
                            // Tiny breath between full batches so concurrent transactions can grab their locks
                            if ($deletedThisBatch === $BATCH_SIZE) usleep(20000); // 20ms
                        }
                        $deletedCounts[$table] = $totalDeleted;
                    }
                } finally {
                    // Always re-enable FK checks, even if a batch ultimately threw
                    \DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }

                // Fresh live counts for ALL Delete-Data cards, so the client can update every
                // count pill without a page reload (a delete in one section can affect others).
                $liveCounts = [
                    'sales'     => (int) \DB::table('customer_invoices')->count(),
                    'purchases' => (int) \DB::table('supplier_invoices')->count(),
                    'products'  => (int) \DB::table('products')->count(),
                    'customers' => (int) \DB::table('customers')->count(),
                    'suppliers' => (int) \DB::table('suppliers')->count(),
                ];

                return response()->json([
                    'success' => true,
                    'message' => ucfirst($section) . ' data deleted successfully.',
                    'deleted' => $deletedCounts,
                    'counts'  => $liveCounts,
                ]);
            })->name('settings.data.delete');

            // Live row counts for the Delete Data cards — lets the client refresh
            // every count pill without a page reload (e.g. after an import).
            Route::get('settings/data-counts', function() {
                return response()->json([
                    'success' => true,
                    'counts'  => [
                        'sales'     => (int) \DB::table('customer_invoices')->count(),
                        'purchases' => (int) \DB::table('supplier_invoices')->count(),
                        'products'  => (int) \DB::table('products')->count(),
                        'customers' => (int) \DB::table('customers')->count(),
                        'suppliers' => (int) \DB::table('suppliers')->count(),
                    ],
                ]);
            })->name('settings.data.counts');

            // Import Data routes
            Route::post('settings/import/headers', [\App\Http\Controllers\ImportDataController::class, 'headers'])->name('settings.import.headers');
            Route::post('settings/import/preview', [\App\Http\Controllers\ImportDataController::class, 'preview'])->name('settings.import.preview');
            Route::post('settings/import/import', [\App\Http\Controllers\ImportDataController::class, 'import'])->name('settings.import.import');
            Route::post('settings/import/debug', [\App\Http\Controllers\ImportDataController::class, 'debug'])->name('settings.import.debug');
            // Lightweight list + quick-add for the Sales-Import "Fix Errors" dropdowns
            Route::get('settings/import/entities/customers', [\App\Http\Controllers\ImportDataController::class, 'listCustomers'])->name('settings.import.entities.customers');
            Route::get('settings/import/entities/suppliers', [\App\Http\Controllers\ImportDataController::class, 'listSuppliers'])->name('settings.import.entities.suppliers');
            Route::post('settings/import/entities/customer-quick-add', [\App\Http\Controllers\ImportDataController::class, 'quickAddCustomer'])->name('settings.import.entities.customer_quick_add');
            Route::post('settings/import/entities/supplier-quick-add', [\App\Http\Controllers\ImportDataController::class, 'quickAddSupplier'])->name('settings.import.entities.supplier_quick_add');
            Route::get('settings/import/entities/products', [\App\Http\Controllers\ImportDataController::class, 'listProducts'])->name('settings.import.entities.products');
            Route::post('settings/import/entities/product-quick-add', [\App\Http\Controllers\ImportDataController::class, 'quickAddProduct'])->name('settings.import.entities.product_quick_add');

            Route::group(['prefix' => 'customers', 'as' => 'customers.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('index', [CustomerController::class, 'index'])->name('index');
					Route::match(['get','post'], 'list', [CustomerController::class, 'list'])->name('list');
                });

                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    Route::get('/create', [CustomerController::class, 'create'])->name('create');
                    Route::post('store', [CustomerController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
                    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
                });

                Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
                });
				
				// customer account payment.
				Route::group(['prefix' => 'on_account_payment', 'as' => 'on_account_payment.'], function(){
					Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
						Route::post('store', [OnAccountPaymentController::class, 'store'])->name('store');
					});
					Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
						//Route::get('index', [SupplierController::class, 'index'])->name('index');
					});
					Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
						//Route::get('index', [SupplierController::class, 'index'])->name('index');
					});
					Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
						Route::get('', [OnAccountPaymentController::class, 'create'])->name('index');
						Route::post('list', [OnAccountPaymentController::class, 'index'])->name('list');
					});
				});

            });
			
            Route::group(['prefix' => 'suppliers', 'as' => 'suppliers.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('index', [SupplierController::class, 'index'])->name('index');
                    Route::match(['get','post'], 'list', [SupplierController::class, 'list'])->name('list');
                });

                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    Route::get('supplier/create', [SupplierController::class, 'create'])->name('create');
                    Route::post('store', [SupplierController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                    Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
                    Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
                });

                Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                    Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
                });
				
				// suppliers.
				Route::group(['prefix' => 'on_account_payment', 'as' => 'on_account_payment.'], function(){
					Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
						Route::post('store', [SupplierPaymentsController::class, 'storeOnAccount'])->name('store');
					});
					Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
						//Route::get('index', [SupplierController::class, 'index'])->name('index');
					});
					Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
						//Route::get('index', [SupplierController::class, 'index'])->name('index');
					});
					Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
						Route::get('', [SupplierPaymentsController::class, 'createOnAccount'])->name('index');
						Route::post('list', [SupplierPaymentsController::class, 'indexOnAccount'])->name('list');
					});
				});

            });

            Route::group(['prefix' => 'products', 'as' => 'products.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('index', [ProductController::class, 'index'])->name('index');
                    Route::get('list', [ProductController::class, 'list'])->name('list');
                });

                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    Route::get('/create', [ProductController::class, 'create'])->name('create');
                    Route::post('store', [ProductController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
                    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                });

                Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
                });
            });

            Route::group(['prefix' => 'passbooks', 'as' => 'passbooks.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('index', [PassbookController::class, 'index'])->name('index');
                });

                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    Route::get('/create', [PassbookController::class, 'create'])->name('create');
                    Route::post('store', [PassbookController::class, 'store'])->name('store');
                });
                Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
                    Route::post('/passbooks-list', [PassbookController::class, 'ajaxPassbooksList'])->name('passbooks-list');

                });
            });
        });
		
		// stock manager.
		Route::get('stock_manager/view', [StockClosingController::class, 'stockManager'])->name('stock_manager.view.index');

		// closing stock.
		Route::group(['prefix' => 'stock_closing', 'as' => 'stock_closing.'], function(){
			Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
				Route::post('save-all', [StockClosingController::class, 'saveAll'])->name('save-all');
				Route::post('save-one', [StockClosingController::class, 'saveOne'])->name('save-one');
			});
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('index', [StockClosingController::class, 'crud'])->name('index');
				Route::post('products', [StockClosingController::class, 'products'])->name('products');
				Route::get('unassigned-suppliers', [StockClosingController::class, 'unassignedSuppliers'])->name('unassigned-suppliers');
				Route::post('unassigned-suppliers/list', [StockClosingController::class, 'unassignedSuppliersList'])->name('unassigned-suppliers.list');
				Route::post('unassigned-suppliers/assign', [StockClosingController::class, 'assignSupplier'])->name('unassigned-suppliers.assign');
				Route::get('unassigned-suppliers/suppliers-by-product/{product_id}', [StockClosingController::class, 'suppliersByProduct'])->name('unassigned-suppliers.suppliers-by-product');
			});
			Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
				Route::post('', [StockClosingController::class, 'update'])->name('edit');
			});
		});
		
		// stock check.
		Route::group(['prefix' => 'stock_check', 'as' => 'stock_check.'], function(){
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [StockCheckController::class, 'index'])->name('index');
				Route::post('list', [StockCheckController::class, 'list'])->name('list');
				Route::post('openingStock', [StockCheckController::class, 'openingStock'])->name('openingStock');
				Route::post('newStock', [StockCheckController::class, 'newStock'])->name('newStock');
				Route::post('sales', [StockCheckController::class, 'sales'])->name('sales');
				Route::post('customerReturn', [StockCheckController::class, 'customerReturn'])->name('customerReturn');
				Route::post('dumps', [StockCheckController::class, 'dumps'])->name('dumps');
				Route::post('supplierReturn', [StockCheckController::class, 'supplierReturn'])->name('supplierReturn');
				Route::post('closingStock', [StockCheckController::class, 'closingStock'])->name('closingStock');
				Route::get('print', [StockCheckController::class, 'print'])->name('print');
			});
		});

		Route::group(['prefix' => 'supplier_payment_history', 'as' => 'supplier_payment_history.'], function(){
			Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
				
			});
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [SupplierPaymentHistoryController::class, 'create'])->name('index');
				Route::get('suppliers', [SupplierPaymentHistoryController::class, 'suppliers'])->name('suppliers');
				Route::post('{supplier_id}', [SupplierPaymentHistoryController::class, 'show'])->name('show');
			});
			Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
				Route::post('{invoice_id}', [SupplierPaymentHistoryController::class, 'destroy'])->name('delete');
			});
		});
		
		// customer return.
		Route::group(['prefix' => 'customer_return', 'as' => 'customer_return.'], function(){
			Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
				
			});
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [CustomerReturnController::class, 'index'])->name('index');
				Route::get('history', [CustomerReturnController::class, 'history'])->name('history');
				Route::get('customers', [CustomerReturnController::class, 'customers'])->name('customers');
				Route::get('products', [CustomerReturnController::class, 'products'])->name('products');
				Route::post('invoices', [CustomerReturnController::class, 'invoices'])->name('invoices');
				Route::post('customer-products', [CustomerReturnController::class, 'customerProducts'])->name('customer-products');
				Route::post('returns', [CustomerReturnController::class, 'returns'])->name('returns');
				Route::post('product', [CustomerReturnController::class, 'product'])->name('product');
				Route::post('return/create', [CustomerReturnController::class, 'returnCreate'])->name('return-create');
				Route::post('return/update', [CustomerReturnController::class, 'returnUpdate'])->name('return-update');
				Route::post('return/delete', [CustomerReturnController::class, 'returnDelete'])->name('return-delete');
				Route::get('credit-balance/{customer_id}', [CustomerReturnController::class, 'creditBalance'])->name('credit-balance');
				Route::get('credit-balance-all', [CustomerReturnController::class, 'creditBalanceAll'])->name('credit-balance-all');
			});
		});

		// supplier return.
		Route::group(['prefix' => 'supplier_return', 'as' => 'supplier_return.'], function(){
			Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
				
			});
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [SupplierReturnController::class, 'index'])->name('index');
				Route::get('history', [SupplierReturnController::class, 'history'])->name('history');
				Route::get('suppliers', [SupplierReturnController::class, 'suppliers'])->name('suppliers');
				Route::get('products', [SupplierReturnController::class, 'products'])->name('products');
				Route::post('invoices', [SupplierReturnController::class, 'invoices'])->name('invoices');
				Route::post('supplier-products', [SupplierReturnController::class, 'supplierProducts'])->name('supplier-products');
				Route::post('returns', [SupplierReturnController::class, 'returns'])->name('returns');
				Route::post('product', [SupplierReturnController::class, 'product'])->name('product');
				Route::post('return/create', [SupplierReturnController::class, 'returnCreate'])->name('return-create');
				Route::post('return/update', [SupplierReturnController::class, 'returnUpdate'])->name('return-update');
				Route::post('return/delete', [SupplierReturnController::class, 'returnDelete'])->name('return-delete');
			Route::get('credit-balance/{supplier_id}', [SupplierReturnController::class, 'creditBalance'])->name('credit-balance');
			Route::get('credit-balance-all', [SupplierReturnController::class, 'creditBalanceAll'])->name('credit-balance-all');
			});
		});
		
		// dump return.
		Route::group(['prefix' => 'dump_return', 'as' => 'dump_return.'], function(){
			Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
				
			});
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [DumpController::class, 'index'])->name('index');
				Route::get('history', [DumpController::class, 'history'])->name('history');
				Route::get('suppliers', [SupplierReturnController::class, 'suppliers'])->name('suppliers');
				Route::get('products', [SupplierReturnController::class, 'products'])->name('products');
				Route::post('supplier-products', [DumpController::class, 'supplierProducts'])->name('supplier-products');
				Route::post('invoices', [DumpController::class, 'invoices'])->name('invoices');
				Route::post('returns', [DumpController::class, 'returns'])->name('returns');
				Route::post('product', [DumpController::class, 'product'])->name('product');
				Route::post('return/create', [DumpController::class, 'returnCreate'])->name('return-create');
				Route::post('return/update', [DumpController::class, 'returnUpdate'])->name('return-update');
				Route::post('return/delete', [DumpController::class, 'returnDelete'])->name('return-delete');
			});
		});
		
		Route::group(['prefix' => 'customer_history', 'as' => 'customer_history.'], function(){
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [CustomerHistoryController::class, 'index'])->name('index');
				Route::get('customers', [CustomerHistoryController::class, 'customers'])->name('customers');
				Route::post('history', [CustomerHistoryController::class, 'history'])->name('history');
				Route::post('email', [CustomerHistoryController::class, 'email'])->name('email');
				Route::post('print', [CustomerHistoryController::class, 'print'])->name('print');
				Route::post('statement', [CustomerHistoryController::class, 'statement'])->name('statement');
			});
		});
		
		Route::group(['prefix' => 'supplier_history', 'as' => 'supplier_history.'], function(){
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [SupplierHistoryController::class, 'index'])->name('index');
				Route::get('suppliers', [SupplierHistoryController::class, 'suppliers'])->name('suppliers');
				Route::post('history', [SupplierHistoryController::class, 'history'])->name('history');
				Route::post('email', [SupplierHistoryController::class, 'email'])->name('email');
				Route::post('print', [SupplierHistoryController::class, 'print'])->name('print');
				Route::post('statement', [SupplierHistoryController::class, 'statement'])->name('statement');
			});
		});
		
		Route::group(['prefix' => 'product_history', 'as' => 'product_history.'], function(){
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [ProductHistoryController::class, 'index'])->name('index');
				Route::get('customers', [ProductHistoryController::class, 'customers'])->name('customers');
				Route::get('suppliers', [ProductHistoryController::class, 'suppliers'])->name('suppliers');
				Route::get('products', [ProductHistoryController::class, 'products'])->name('products');
				
				Route::post('supplier_invoices', [ProductHistoryController::class, 'supplierInvoices'])->name('supplier_invoices');
				Route::post('supplier_returns', [ProductHistoryController::class, 'supplierReturns'])->name('supplier_returns');
				Route::post('sales', [ProductHistoryController::class, 'sales'])->name('sales');
				Route::post('customer_returns', [ProductHistoryController::class, 'customerReturns'])->name('customer_returns');
				Route::post('dumps', [ProductHistoryController::class, 'dumps'])->name('dumps');
			});
		});
		
		Route::group(['prefix' => 'product_profit_loss', 'as' => 'product_profit_loss.'], function(){
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [ProfitLossController::class, 'index'])->name('index');
				Route::get('customers', [ProfitLossController::class, 'customers'])->name('customers');
				Route::get('suppliers', [ProfitLossController::class, 'suppliers'])->name('suppliers');
				Route::get('products', [ProfitLossController::class, 'products'])->name('products');
				Route::post('profit_loss', [ProfitLossController::class, 'profitLoss'])->name('profit_loss');
				
				/*Route::post('supplier_invoices', [ProductHistoryController::class, 'supplierInvoices'])->name('supplier_invoices');
				Route::post('supplier_returns', [ProductHistoryController::class, 'supplierReturns'])->name('supplier_returns');
				Route::post('sales', [ProductHistoryController::class, 'sales'])->name('sales');
				Route::post('customer_returns', [ProductHistoryController::class, 'customerReturns'])->name('customer_returns');
				Route::post('dumps', [ProductHistoryController::class, 'dumps'])->name('dumps');*/
			});
		});
		

        //for admin editing
        Route::group(['prefix' => 'admin', 'as' => 'admin.'], function(){

          Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
              Route::get('/profile', [AdminController::class, 'edit'])->name('profile');
              Route::post('/update', [AdminController::class, 'update'])->name('update');

          });
        });
		
		Route::group(['prefix' => 'general_settings', 'as' => 'general_settings.'], function(){
			Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
				Route::get('', [GeneralSettingController::class, 'index'])->name('index');
				Route::get('list', [GeneralSettingController::class, 'list'])->name('list');
			});
			Route::group(['prefix' => 'save', 'as' => 'save.'], function(){
				Route::post('', [GeneralSettingController::class, 'update'])->name('save');
			});
		});
		
		Route::group(['prefix' => 'payments', 'as' => 'payments.'], function(){
			
			// customers.
			Route::group(['prefix' => 'customer_payment', 'as' => 'customer_payment.'], function(){
				Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
					//Route::post('store', [CustomerPaymentsController::class, 'store'])->name('store');
					Route::post('unpaid-invoices/{customer_id}', [CustomerPaymentsController::class, 'unpaidInvoices'])->name('unpaid-invoices');
					Route::post('pay/{customer_id}', [CustomerPaymentsController::class, 'save'])->name('pay-invoices');
					Route::get('on-account-payments/{customer_id}', [CustomerPaymentsController::class, 'onAccountPayments'])->name('on-account-payments');
				});
				Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
					//Route::get('index', [CustomerPaymentsController::class, 'index'])->name('index');
				});
				Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
					//Route::get('index', [CustomerPaymentsController::class, 'index'])->name('index');
				});
				Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
					Route::get('', [CustomerPaymentsController::class, 'create'])->name('index');
					//Route::post('list', [CustomerPaymentsController::class, 'index'])->name('list');
				});
			});
			
			// suppliers.
			Route::group(['prefix' => 'supplier_payment', 'as' => 'supplier_payment.'], function(){
				Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
					//Route::post('store', [SupplierPaymentsController::class, 'store'])->name('store');
					Route::post('unpaid-invoices/{supplier_id}', [SupplierPaymentsController::class, 'unpaidInvoices'])->name('unpaid-invoices');
					Route::post('pay/{supplier_id}', [SupplierPaymentsController::class, 'save'])->name('pay-invoices');
					Route::get('suppliers/list', [SupplierPaymentsController::class, 'listSuppliers'])->name('list-suppliers');
					Route::get('on-account-payments/{supplier_id}', [SupplierPaymentsController::class, 'onAccountPayments'])->name('on-account-payments');
				});
				Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
					//Route::get('index', [SupplierPaymentsController::class, 'index'])->name('index');
				});
				Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
					//Route::get('index', [SupplierPaymentsController::class, 'index'])->name('index');
				});
				Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
					Route::get('', [SupplierPaymentsController::class, 'create'])->name('index');
					//Route::post('list', [SupplierPaymentsController::class, 'index'])->name('list');
				});
			});
		});
		
        // end admin edit

        Route::group(['prefix' => 'data_entry', 'as' => 'data_entry.'], function(){
            Route::group(['prefix' => 'sales_entry', 'as' => 'sales_entry.'], function(){
			
				// email group.
				Route::group(['prefix' => 'invoice_email', 'as' => 'invoice_email.'], function(){
                    Route::group(['prefix' => 'send', 'as' => 'send.'], function(){
						Route::match(['get','post'], '/{invoice}', [\App\Http\Controllers\EmailsController::class, 'customerInvoice'])->name('send');
					});
                });
				
				// payment group.
				Route::group(['prefix' => 'invoice_payment', 'as' => 'invoice_payment.'], function(){
                    Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
						Route::post('', [\App\Http\Controllers\CustomerPaymentsController::class, 'store'])->name('create');
					});
					Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
						Route::post('', [\App\Http\Controllers\CustomerPaymentsController::class, 'update'])->name('update');
					});
					Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
						Route::post('', [\App\Http\Controllers\CustomerPaymentsController::class, 'destroy'])->name('delete');
					});
					Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
						Route::get('/{id?}', [\App\Http\Controllers\CustomerPaymentsController::class, 'index'])->name('list');
					});
                });
				
				
				//statements
				Route::group(['prefix' => 'statements', 'as' => 'statements.'], function(){
                    Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
						Route::get('', [\App\Http\Controllers\CustomerPaymentsController::class, 'statements'])->name('view');
					});
                });
			
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    //Route::get('/index', [SalesController::class, 'index'])->name('index');
					Route::get('/', [SalesController::class, 'create'])->name('index');
                });
                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    //Route::get('/', [SalesController::class, 'create'])->name('index');
                    Route::post('/store', [SalesController::class, 'store'])->name('store');
                    Route::post('grid', [SalesController::class, 'grid'])->name('grid');
                });
                Route::group(['prefix' => 'invoice', 'as' => 'invoice.'], function(){
                    Route::get('/', [SalesController::class, 'newInvoiceForm'])->name('new');
                    Route::post('/generate', [SalesController::class, 'generateSalesInvoice'])->name('generate');
                    Route::get('/{invoice}', [SalesController::class, 'edit'])->name('index');
                    Route::post('/store-products/{invoice}', [SalesController::class, 'storeProducts'])->name('store');
                    Route::get('invoiceview/{invoice}', [SalesController::class, 'invoiceview'])->name('invoiceview');
					Route::get('invoiceview-delivery/{invoice}', [SalesController::class, 'invoiceviewDelivery'])->name('invoiceview-delivery');
                    Route::get('invoicedownload/{invoice}', [SalesController::class, 'invoicedownload'])->name('invoicedownload');
                    Route::get('invoiceexcel/{invoice}', [SalesController::class, 'invoiceExcel'])->name('invoiceexcel');
                    Route::get('mail/{invoice}', [SalesController::class, 'mail'])->name('mail');
                    Route::get('delete/{invoice}', [SalesController::class, 'delete'])->name('delete');
                });

                Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
                    Route::get('/product-list', [SalesController::class, 'ajaxProductsList'])->name('product-list');
					Route::get('/supplier-list/{product_id}', [SalesController::class, 'ajaxSuppliersList'])->name('supplier-list');
					Route::get('/supplier-list-all', [SalesController::class, 'ajaxSuppliersListAll'])->name('supplier-list-all');
					Route::get('/product-supplier-invoices/{product_id}/{supplier_id}', [SalesController::class, 'productSupplierInvoices'])->name('product-supplier-invoices');
                    Route::get('/payment-list', [SalesController::class, 'ajaxPaymentsList'])->name('payment-list');
                    Route::post('/add-invoice', [SalesController::class, 'ajaxCreateInvoice'])->name('add-invoice');
                    Route::post('/add-single-invoice', [SalesController::class, 'ajaxCreateSingalInvoicenew'])->name('add-single-invoice');
                    Route::post('/delete-single-invoice', [SalesController::class, 'ajaxDeleteSingalInvoice'])->name('delete-single-invoice');
                    Route::post('/fetch-invoice-detail', [SalesController::class, 'ajaxfetchInvoiceDetail'])->name('fetch-invoice-detail');
                    Route::get('/invoice-detail/{id}', [SalesController::class, 'ajaxfetchInvoiceAllDetail'])->name('invoice-detail');
                    Route::post('/edit-single-invoice', [SalesController::class, 'ajaxEditSingleInvoice'])->name('edit-single-invoice');
                    Route::post('/update-payment', [SalesController::class, 'ajaxEditPayment'])->name('update-payment');
                    Route::post('/update-invoice-detail', [SalesController::class, 'ajaxEditInvoiceDetail'])->name('update-invoice-detail');
                    Route::post('/save-invoice-notes', [SalesController::class, 'saveInvoiceNotes'])->name('save-invoice-notes');
                    Route::get('/fetchuser', [SalesController::class, 'fetchuser'])->name('fetchuser');

                });
                Route::group(['prefix' => 'sales_customer', 'as' => 'sales_customer.'], function(){
                    Route::get('/ajaxCustomer', [SalesController::class, 'ajaxCustomer'])->name('ajaxCustomer');
                });
                Route::group(['prefix' => 'sales_date', 'as' => 'sales_date.'], function(){
                    Route::get('/ajaxDate', [SalesController::class, 'ajaxDate'])->name('ajaxDate');
                });
            });

            Route::group(['prefix' => 'purchase_entry', 'as' => 'purchase_entry.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    //Route::get('/index', [SalesController::class, 'index'])->name('index');
					Route::get('/', [PurchaseController::class, 'create'])->name('index');
                });
                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    //
                    Route::post('/store', [PurchaseController::class, 'store'])->name('store');
                    Route::post('grid', [SalesController::class, 'grid'])->name('grid');
                });
                Route::group(['prefix' => 'invoice', 'as' => 'invoice.'], function(){
                    Route::get('/', [PurchaseController::class, 'newInvoiceForm'])->name('new');
                    Route::post('/generate', [PurchaseController::class, 'generateInvoice'])->name('generate');
                    Route::get('/products/list', [PurchaseController::class, 'ajaxProductsList'])->name('products-list');
                    Route::get('/{invoice}', [PurchaseController::class, 'edit'])->name('index');
                    Route::post('/store-products/{invoice}', [SalesController::class, 'storeProducts'])->name('store');
                    Route::get('invoiceview/{invoice}', [PurchaseController::class, 'invoiceview'])->name('invoiceview');
                    Route::get('invoicedownload/{invoice}', [PurchaseController::class, 'invoicedownload'])->name('invoicedownload');
                    Route::get('invoiceexcel/{invoice}', [PurchaseController::class, 'invoiceExcel'])->name('invoiceexcel');
                    Route::get('mail/{invoice}', [PurchaseController::class, 'mail'])->name('mail');
                    Route::get('delete/{invoice}', [PurchaseController::class, 'delete'])->name('delete');
                });
				
				Route::group(['prefix' => 'change_other_invoice', 'as' => 'change_other_invoice.'], function(){
					Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
						Route::post('', [\App\Http\Controllers\PurchaseController::class, 'changeOtherInvoiceId'])->name('update');
					});
				});
				Route::group(['prefix' => 'change_supplier', 'as' => 'change_supplier.'], function(){
					Route::post('', [\App\Http\Controllers\PurchaseController::class, 'changeSupplier'])->name('update');
				});
				// email group (purchase invoice).
				Route::group(['prefix' => 'invoice_email', 'as' => 'invoice_email.'], function(){
					Route::group(['prefix' => 'send', 'as' => 'send.'], function(){
						Route::match(['get','post'], '/{invoice}', [\App\Http\Controllers\EmailsController::class, 'supplierInvoice'])->name('send');
					});
				});
				// payment group.
				Route::group(['prefix' => 'invoice_payment', 'as' => 'invoice_payment.'], function(){
                    Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
						Route::post('', [\App\Http\Controllers\SupplierPaymentsController::class, 'store'])->name('create');
					});
					Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
						Route::post('', [\App\Http\Controllers\SupplierPaymentsController::class, 'update'])->name('update');
					});
					Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
						//Route::post('', [\App\Http\Controllers\SupplierPaymentsController::class, 'destroy'])->name('delete');
					});
					Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
						Route::get('/{id}', [\App\Http\Controllers\SupplierPaymentsController::class, 'index'])->name('list');
					});
                });
				
				Route::group(['prefix' => 'statements', 'as' => 'statements.'], function(){
                    Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
						Route::get('', [\App\Http\Controllers\SupplierPaymentsController::class, 'statements'])->name('view');
					});
                });
				
                Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
                    Route::get('/product-list', [PurchaseController::class, 'ajaxProductsList'])->name('product-list');
                    Route::post('/add-invoice', [PurchaseController::class, 'ajaxCreateInvoice'])->name('add-invoice');
                    Route::post('/add-single-invoice', [PurchaseController::class, 'ajaxCreateSingalInvoicenew'])->name('add-single-invoice');
                    Route::post('/delete-single-invoice', [PurchaseController::class, 'ajaxDeleteSingalInvoice'])->name('delete-single-invoice');
                    Route::post('/fetch-invoice-detail', [PurchaseController::class, 'ajaxfetchInvoiceDetail'])->name('fetch-invoice-detail');
                    Route::post('/edit-single-invoice', [PurchaseController::class, 'ajaxEditSingleInvoice'])->name('edit-single-invoice');
                    Route::get('/invoice-detail/{id}', [PurchaseController::class, 'ajaxfetchInvoiceAllDetail'])->name('invoice-detail');
					Route::post('/add-invoice-stock', [PurchaseController::class, 'addStockInvoice'])->name('add-invoice-stock');
					Route::post('/delete-invoice-products', [PurchaseController::class, 'deleteInvoiceProducts'])->name('delete-invoice-products');
                });
            });
            Route::group(['prefix' => 'closing_stock', 'as' => 'closing_stock.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('/index', [SalesController::class, 'index'])->name('index');
                });
            });

        });

        Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function(){
            Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                Route::get('/index', [HomeController::class, 'index'])->name('index');
                Route::get('/chart-data', [HomeController::class, 'chartData'])->name('chart_data');
                Route::get('/stats', [HomeController::class, 'dashboardStats'])->name('stats');
            });
        });
        // Route::resource('permissionModule', PermissionModule::class);
        // Route::resource('permissionGroup', PermissionGroup::class);
        // Route::resource('role', RoleController::class);
        // Route::resource('permissionRole', PermissionRoleController::class);
        // Route::resource('user', UserController::class);
        // Route::resource('customer', CustomerController::class);
        // Route::resource('supplier', SupplierController::class);
        // Route::resource('product', ProductController::class);
        // Route::resource('menu', MenuController::class);
        // Route::resource('menuRole', MenuRoleController::class);
       // Route::get('/CompanyDetails/index', [CompanyDetails::class, 'index'])->name('index');
       // Route::get('/CompanyDetails/create', [CompanyDetails::class, 'create'])->name('CompanyDetails.create');

        Route::group(['prefix' => 'historical_reports', 'as' => 'historical_reports.'], function(){
         Route::group(['prefix' => 'customer_history', 'as' => 'customer_history.'], function(){
           Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
               Route::get('/index', [HistoricalReportsController::class, 'index'])->name('index');
           });
           Route::get('/customer_invoice_print', [HistoricalReportsController::class, 'customer_invoice_print'])->name('customer_invoice_print');
            Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
               Route::post('/customer_history', [HistoricalReportsController::class, 'customerHistory'])->name('customer_history');
               Route::post('/customer_history_pdf', [HistoricalReportsController::class, 'customerhistorypdf'])->name('customer_history_pdf');
            });
			
			/** print, email, download & statement */
			Route::group(['prefix' => 'report', 'as' => 'report.'], function(){
               Route::get('/document/{type}', [ReportActionsController::class, 'customerCommonAction'])->name('download-pdf');
			});
			
          });
        });
        Route::group(['prefix' => 'daily_report', 'as' => 'daily_report.'], function(){
            Route::group(['prefix' => 'daily_book_sales', 'as' => 'daily_book_sales.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('/index', [SalesController::class, 'dailyBookSales'])->name('index');
					Route::get('customers', [SalesController::class, 'customers'])->name('customers');
                    Route::post('list', [SalesController::class, 'list'])->name('list');
                    Route::get('print', [SalesController::class, 'print'])->name('print');
                    Route::match(['get','post'], 'email', [SalesController::class, 'emailDailyBookSales'])->name('email');
                    Route::get('statement', [SalesController::class, 'statementDailyBookSales'])->name('statement');
                });

                Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
                    Route::post('/sales-list', [SalesController::class, 'ajaxDailyBookSales'])->name('sales-list');
                });
            });

            Route::group(['prefix' => 'daily_book_purchase', 'as' => 'daily_book_purchase.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('/index', [PurchaseController::class, 'dailyBookSales'])->name('index');
                    Route::get('suppliers', [PurchaseController::class, 'suppliers'])->name('suppliers');
                    Route::post('list', [PurchaseController::class, 'list'])->name('list');
                    Route::get('print', [PurchaseController::class, 'print'])->name('print');
                    Route::get('statement', [PurchaseController::class, 'statementDailyBookPurchase'])->name('statement');
                    Route::match(['get','post'], 'email', [PurchaseController::class, 'emailDailyBookPurchase'])->name('email');
                });
                Route::group(['prefix' => 'ajax', 'as' => 'ajax.'], function(){
                    Route::post('/purchases-list', [PurchaseController::class, 'ajaxDailyBookPurchase'])->name('purchases-list');
                });
            });
        });
        Route::group(['prefix' => 'settings', 'as' => 'settings.'], function(){
            Route::group(['prefix' => 'payment_methods', 'as' => 'payment_methods.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('index', [PaymentController::class, 'index'])->name('index');
                });

                Route::group(['prefix' => 'create', 'as' => 'create.'], function(){
                    Route::get('/create', [PaymentController::class, 'create'])->name('create');
                    Route::post('store', [PaymentController::class, 'store'])->name('store');
                });

                Route::group(['prefix' => 'edit', 'as' => 'edit.'], function(){
                    Route::get('/{customer}/edit', [PaymentController::class, 'edit'])->name('edit');
                    Route::put('/{customer}', [PaymentController::class, 'update'])->name('update');
                });

                Route::group(['prefix' => 'delete', 'as' => 'delete.'], function(){
                    Route::delete('/{customer}', [PaymentController::class, 'destroy'])->name('destroy');
                });
            });
        });

        // activity log.
        Route::group(['prefix' => 'activity_logs', 'as' => 'activity_logs.'], function(){
            Route::group(['prefix' => 'users', 'as' => 'users.'], function(){
                Route::group(['prefix' => 'view', 'as' => 'view.'], function(){
                    Route::get('/', [ActivityLogController::class, 'index'])->name('index');
                    Route::post('list', [ActivityLogController::class, 'list'])->name('list');
                    Route::get('users', [ActivityLogController::class, 'users'])->name('users');
                    Route::get('events', [ActivityLogController::class, 'events'])->name('events');
                });
            });
        });

    });
});

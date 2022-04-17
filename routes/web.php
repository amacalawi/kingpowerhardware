<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\UnitOfMeasurementController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PaymentTermsController;
use App\Http\Controllers\PurchaseOrderTypeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\TransferItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DeliveryReportsController;
use App\Http\Controllers\PurchasedReportsController;
use App\Http\Controllers\SalesItemReportsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransferItemReportsController;
use App\Http\Controllers\CollectionReportsController;

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

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/auth/dashboard');
    }
    return view('auth/login');
});

Route::get('/logout', [App\Http\Controllers\DashboardController::class, 'logout'])->name('logout');
Auth::routes();

Route::prefix('auth')->group(function () {
    
    /* Dashboard Routes */
    Route::prefix('dashboard')->group(function () {
        Route::get('', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    });

    /* Delivery Routes */
    Route::prefix('billing')->group(function () {
        Route::get('',[BillingController::class, 'index']);
        Route::get('all-active',[BillingController::class, 'all_active']);
        Route::get('all-active-billing-lines',[BillingController::class, 'all_active_billing_lines']);
        Route::get('all-active-payment-lines',[BillingController::class, 'all_active_payment_lines']);
        Route::get('get-invoice-no/{branch?}/{invoice?}',[BillingController::class, 'get_invoice_no']);
        Route::get('get-customer-info/{customer?}',[BillingController::class, 'get_customer_info']);
        Route::get('get-due-date/{terms?}/{invoice_date?}',[BillingController::class, 'get_due_date']);
        Route::post('store',[BillingController::class, 'store']);
        Route::put('update/{id?}',[BillingController::class, 'update']);
        Route::get('find/{id?}',[BillingController::class, 'find']);
        Route::get('all-active-unbilled-lines',[BillingController::class, 'all_active_unbilled_lines']);
        Route::post('attach/{id?}',[BillingController::class, 'attach']);
        Route::put('remove-billing-line/{id?}',[BillingController::class, 'remove_billing_line']);
        Route::get('get-bank-info/{id?}',[BillingController::class, 'get_bank_info']);
        Route::post('store-payment-line/{id?}',[BillingController::class, 'store_payment_line']);
        Route::put('update-payment-line/{id?}',[BillingController::class, 'update_payment_line']);
        Route::get('find-payment-line/{id?}',[BillingController::class, 'find_payment_line']);
        Route::put('remove-payment-line/{id?}',[BillingController::class, 'remove_payment_line']);
        Route::put('post-payment-line/{id?}',[BillingController::class, 'post_payment_line']);
    });

    /* Delivery Routes */
    Route::prefix('delivery')->group(function () {
        Route::get('',[DeliveryController::class, 'index']);
        Route::post('store',[DeliveryController::class, 'store']);
        Route::put('update/{id?}',[DeliveryController::class, 'update']);
        Route::get('all-active',[DeliveryController::class, 'all_active']);
        Route::get('all-active-lines',[DeliveryController::class, 'all_active_lines']);
        Route::get('get-delivery-doc-no/{id?}',[DeliveryController::class, 'get_delivery_doc_no']);
        Route::get('get-customer-info/{id?}',[DeliveryController::class, 'get_customer_info']);
        Route::get('get-item-srp/{item?}/{branch?}',[DeliveryController::class, 'get_item_srp']);
        Route::get('find/{id?}',[DeliveryController::class, 'find']);
        Route::post('store-line-item/{id?}',[DeliveryController::class, 'store_line_item']);
        Route::put('remove-line-item/{id?}',[DeliveryController::class, 'remove_line_item']);
        Route::put('update-line-item/{id?}',[DeliveryController::class, 'update_line_item']);
        Route::get('find-line-item/{id?}',[DeliveryController::class, 'find_line_item']);
        Route::post('post-line-item/{id?}',[DeliveryController::class, 'post_line_item']);
        Route::get('preview',[DeliveryController::class, 'preview']);
        Route::get('export',[DeliveryController::class, 'export']);
        Route::get('find-line/{id?}',[DeliveryController::class, 'find_line']);
    });

    /* Purchase Order Routes */
    Route::prefix('purchase-order')->group(function () {
        Route::get('',[PurchaseOrderController::class, 'index']);
        Route::post('store',[PurchaseOrderController::class, 'store']);
        Route::put('update/{id?}',[PurchaseOrderController::class, 'update']);
        Route::get('all-active',[PurchaseOrderController::class, 'all_active']);
        Route::get('all-active-lines',[PurchaseOrderController::class, 'all_active_lines']);
        Route::get('get-supplier-info/{id?}',[PurchaseOrderController::class, 'get_supplier_info']);
        Route::get('get-po-no/{id?}',[PurchaseOrderController::class, 'get_po_no']);
        Route::get('find/{id?}',[PurchaseOrderController::class, 'find']);
        Route::get('get-item-info/{item?}/{branch?}',[PurchaseOrderController::class, 'get_item_info']);
        Route::post('store-line-item/{id?}',[PurchaseOrderController::class, 'store_line_item']);
        Route::put('remove-line-item/{id?}',[PurchaseOrderController::class, 'remove_line_item']);
        Route::put('update-line-item/{id?}',[PurchaseOrderController::class, 'update_line_item']);
        Route::get('find-line-item/{id?}',[PurchaseOrderController::class, 'find_line_item']);
        Route::post('post-line-item/{id?}',[PurchaseOrderController::class, 'post_line_item']);
        Route::get('find-line/{id?}',[PurchaseOrderController::class, 'find_line']);
        Route::get('preview',[PurchaseOrderController::class, 'preview']);
        Route::get('export',[PurchaseOrderController::class, 'export']);
    });

    Route::prefix('items')->group(function () {
        Route::prefix('listing')->group(function () {
            Route::get('',[ItemController::class, 'index']);
            Route::get('all-active',[ItemController::class, 'all_active']);
            Route::get('inactive',[ItemController::class, 'inactive']);
            Route::get('all-inactive',[ItemController::class, 'all_inactive']);
            Route::get('find/{id?}',[ItemController::class, 'find']);
            Route::post('store',[ItemController::class, 'store']);
            Route::put('update/{id?}',[ItemController::class, 'update']);
            Route::get('export',[ItemController::class, 'export']);
            Route::post('import',[ItemController::class, 'import']);
            Route::put('remove/{id?}',[ItemController::class, 'remove']);
            Route::get('generate-item-code',[ItemController::class, 'generate_item_code']);
            Route::get('get-all-inventory/{id?}',[ItemController::class, 'get_all_inventory']);
            Route::get('all-active-inventory',[ItemController::class, 'all_active_inventory']);
            Route::get('find-item-quantity/{itemID?}/{branchId?}',[ItemController::class, 'find_item_quantity']);
            Route::post('store-withdrawal',[ItemController::class, 'store_withdrawal']);
            Route::post('store-receiving',[ItemController::class, 'store_receiving']);
        });
        
        Route::prefix('inventory-adjustment')->group(function () {
            Route::get('',[AdjustmentController::class, 'index']);
            Route::get('all-active',[AdjustmentController::class, 'all_active']);
            Route::post('store',[AdjustmentController::class, 'store']);
            Route::get('get-item-info/{item?}/{branch?}',[AdjustmentController::class, 'get_item_info']);
        });

        Route::prefix('transfer-items')->group(function () {
            Route::get('',[TransferItemController::class, 'index']);
            Route::get('all-active',[TransferItemController::class, 'all_active']);
            Route::post('store',[TransferItemController::class, 'store']);
            Route::put('update/{id?}',[TransferItemController::class, 'update']);
            Route::get('generate-trans-no',[TransferItemController::class, 'generate_trans_no']);
            Route::get('all-active-lines',[TransferItemController::class, 'all_active_lines']);
            Route::get('find/{id?}',[TransferItemController::class, 'find']);
            Route::get('get-item-info/{item?}/{branch?}',[TransferItemController::class, 'get_item_info']);
            Route::post('store-line-item/{id?}',[TransferItemController::class, 'store_line_item']);
            Route::put('remove-line-item/{id?}',[TransferItemController::class, 'remove_line_item']);
            Route::put('update-line-item/{id?}',[TransferItemController::class, 'update_line_item']);
            Route::get('find-line-item/{id?}',[TransferItemController::class, 'find_line_item']);
            Route::get('find-line/{id?}',[TransferItemController::class, 'find_line']);
            Route::post('post-line-item/{id?}',[TransferItemController::class, 'post_line_item']);
            Route::get('preview',[TransferItemController::class, 'preview']);
        });
    });

    /* Components Routes */
    Route::prefix('components')->group(function () {

        Route::prefix('customers')->group(function () {
            Route::get('',[CustomerController::class, 'index']);
            Route::get('all-active',[CustomerController::class, 'all_active']);
            Route::get('find/{id?}',[CustomerController::class, 'find']);
            Route::post('store',[CustomerController::class, 'store']);
            Route::put('update/{id?}',[CustomerController::class, 'update']);
            Route::get('export',[CustomerController::class, 'export']);
            Route::post('import',[CustomerController::class, 'import']);
            Route::put('remove/{id?}',[CustomerController::class, 'remove']);
            Route::put('restore/{id?}',[CustomerController::class, 'restore']);
        });

        Route::prefix('unit-of-measurements')->group(function () {
            Route::get('',[UnitOfMeasurementController::class, 'index']);
            Route::get('all-active',[UnitOfMeasurementController::class, 'all_active']);
            Route::get('inactive',[UnitOfMeasurementController::class, 'inactive']);
            Route::get('all-inactive',[UnitOfMeasurementController::class, 'all_inactive']);
            Route::get('find/{id?}',[UnitOfMeasurementController::class, 'find']);
            Route::post('store',[UnitOfMeasurementController::class, 'store']);
            Route::put('update/{id?}',[UnitOfMeasurementController::class, 'update']);
            Route::get('export',[UnitOfMeasurementController::class, 'export']);
            Route::get('import',[UnitOfMeasurementController::class, 'import']);
            Route::put('remove/{id?}',[UnitOfMeasurementController::class, 'remove']);
            Route::put('restore/{id?}',[UnitOfMeasurementController::class, 'restore']);
        });

        

        Route::prefix('branches')->group(function () {
            Route::get('',[BranchController::class, 'index']);
            Route::get('all-active',[BranchController::class, 'all_active']);
            Route::get('inactive',[BranchController::class, 'inactive']);
            Route::get('all-inactive',[BranchController::class, 'all_inactive']);
            Route::get('find/{id?}',[BranchController::class, 'find']);
            Route::post('store',[BranchController::class, 'store']);
            Route::put('update/{id?}',[BranchController::class, 'update']);
            Route::get('export',[BranchController::class, 'export']);
            Route::post('import',[BranchController::class, 'import']);
            Route::put('remove/{id?}',[BranchController::class, 'remove']);
            Route::put('restore/{id?}',[BranchController::class, 'restore']);
        });

        Route::prefix('payment-terms')->group(function () {
            Route::get('',[PaymentTermsController::class, 'index']);
            Route::get('all-active',[PaymentTermsController::class, 'all_active']);
            Route::get('inactive',[PaymentTermsController::class, 'inactive']);
            Route::get('all-inactive',[PaymentTermsController::class, 'all_inactive']);
            Route::get('find/{id?}',[PaymentTermsController::class, 'find']);
            Route::post('store',[PaymentTermsController::class, 'store']);
            Route::put('update/{id?}',[PaymentTermsController::class, 'update']);
            Route::get('export',[PaymentTermsController::class, 'export']);
            Route::post('import',[PaymentTermsController::class, 'import']);
            Route::put('remove/{id?}',[PaymentTermsController::class, 'remove']);
            Route::put('restore/{id?}',[PaymentTermsController::class, 'restore']);
        });
        
        Route::prefix('purchase-order-types')->group(function () {
            Route::get('',[PurchaseOrderTypeController::class, 'index']);
            Route::get('all-active',[PurchaseOrderTypeController::class, 'all_active']);
            Route::get('inactive',[PurchaseOrderTypeController::class, 'inactive']);
            Route::get('all-inactive',[PurchaseOrderTypeController::class, 'all_inactive']);
            Route::get('find/{id?}',[PurchaseOrderTypeController::class, 'find']);
            Route::post('store',[PurchaseOrderTypeController::class, 'store']);
            Route::put('update/{id?}',[PurchaseOrderTypeController::class, 'update']);
            Route::get('export',[PurchaseOrderTypeController::class, 'export']);
            Route::post('import',[PurchaseOrderTypeController::class, 'import']);
            Route::put('remove/{id?}',[PurchaseOrderTypeController::class, 'remove']);
            Route::put('restore/{id?}',[PurchaseOrderTypeController::class, 'restore']);
        });

        
        Route::prefix('roles')->group(function () {
            Route::get('',[RoleController::class, 'index']);
            Route::get('all-active',[RoleController::class, 'all_active']);
            Route::get('inactive',[RoleController::class, 'inactive']);
            Route::get('all-inactive',[RoleController::class, 'all_inactive']);
            Route::get('find/{id?}',[RoleController::class, 'find']);
            Route::post('store',[RoleController::class, 'store']);
            Route::put('update/{id?}',[RoleController::class, 'update']);
            Route::get('export',[RoleController::class, 'export']);
            Route::post('import',[RoleController::class, 'import']);
            Route::put('remove/{id?}',[RoleController::class, 'remove']);
            Route::put('restore/{id?}',[RoleController::class, 'restore']);
        });

        Route::prefix('users')->group(function () {
            Route::get('',[UserController::class, 'index']);
            Route::get('all-active',[UserController::class, 'all_active']);
            Route::get('inactive',[UserController::class, 'inactive']);
            Route::get('all-inactive',[UserController::class, 'all_inactive']);
            Route::get('find/{id?}',[UserController::class, 'find']);
            Route::post('store',[UserController::class, 'store']);
            Route::put('update/{id?}',[UserController::class, 'update']);
            Route::get('export',[UserController::class, 'export']);
            Route::post('import',[UserController::class, 'import']);
            Route::put('remove/{id?}',[UserController::class, 'remove']);
            Route::put('restore/{id?}',[UserController::class, 'restore']);
        });

        Route::prefix('suppliers')->group(function () {
            Route::get('',[SupplierController::class, 'index']);
            Route::get('all-active',[SupplierController::class, 'all_active']);
            Route::get('inactive',[SupplierController::class, 'inactive']);
            Route::get('all-inactive',[SupplierController::class, 'all_inactive']);
            Route::get('find/{id?}',[SupplierController::class, 'find']);
            Route::post('store',[SupplierController::class, 'store']);
            Route::put('update/{id?}',[SupplierController::class, 'update']);
            Route::get('export',[SupplierController::class, 'export']);
            Route::post('import',[SupplierController::class, 'import']);
            Route::put('remove/{id?}',[SupplierController::class, 'remove']);
            Route::put('restore/{id?}',[SupplierController::class, 'restore']);
        });
    });

    /* Reports Routes */
    Route::prefix('reports')->group(function () {
        Route::prefix('delivery-reports')->group(function () {
            Route::get('',[DeliveryReportsController::class, 'index']);
            Route::get('search',[DeliveryReportsController::class, 'search']);
            Route::get('export',[DeliveryReportsController::class, 'export']);
        });

        Route::prefix('purchased-reports')->group(function () {
            Route::get('',[PurchasedReportsController::class, 'index']);
            Route::get('search',[PurchasedReportsController::class, 'search']);
            Route::get('export',[PurchasedReportsController::class, 'export']);
        });

        Route::prefix('sales-item-reports')->group(function () {
            Route::get('',[SalesItemReportsController::class, 'index']);
            Route::get('search',[SalesItemReportsController::class, 'search']);
            Route::get('export',[SalesItemReportsController::class, 'export']);
        });

        Route::prefix('transfer-item-reports')->group(function () {
            Route::get('',[TransferItemReportsController::class, 'index']);
            Route::get('search',[TransferItemReportsController::class, 'search']);
            Route::get('export',[TransferItemReportsController::class, 'export']);
        });

        Route::prefix('collection-reports')->group(function () {
            Route::get('',[CollectionReportsController::class, 'index']);
            Route::get('search',[CollectionReportsController::class, 'search']);
            Route::get('export',[CollectionReportsController::class, 'export']);
        });
    });
});

// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

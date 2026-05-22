<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApiRunnerController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\CSVController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\EmailsController;

// api runner for testing
Route::get('/api-runner', [ApiRunnerController::class, 'index']);
Route::post('/api-runner/run', [ApiRunnerController::class, 'run'])->name('api.runner');

Route::group(['prefix' => 'pdf', 'as' => 'pdf.'], function(){
    Route::get('customer_history', [PDFController::class, 'customerHistory'])->name('customer_history');
    Route::get('supplier_history', [PDFController::class, 'supplierHistory'])->name('supplier_history');
});

Route::group(['prefix' => 'excel', 'as' => 'excel.'], function(){
    Route::get('customer_history', [ExcelController::class, 'customerHistory'])->name('customer_history');
    Route::get('supplier_history', [ExcelController::class, 'supplierHistory'])->name('supplier_history');
    Route::get('product_history',  [ExcelController::class, 'productHistory'])->name('product_history');
    Route::get('products',         [ExcelController::class, 'products'])->name('products');
    Route::get('stock_check',      [ExcelController::class, 'stockCheck'])->name('stock_check');
    Route::get('stock_closing',    [ExcelController::class, 'stockClosing'])->name('stock_closing');
    Route::get('stock_manager',    [ExcelController::class, 'stockManager'])->name('stock_manager');
    Route::get('unassigned_suppliers', [ExcelController::class, 'unassignedSuppliers'])->name('unassigned_suppliers');
    Route::get('customer_return',  [ExcelController::class, 'customerReturn'])->name('customer_return');
    Route::get('customer_returnable', [ExcelController::class, 'customerReturnable'])->name('customer_returnable');
    Route::get('supplier_return',  [ExcelController::class, 'supplierReturn'])->name('supplier_return');
    Route::get('supplier_returnable', [ExcelController::class, 'supplierReturnable'])->name('supplier_returnable');
    Route::get('dump',             [ExcelController::class, 'dump'])->name('dump');
    Route::get('dumpable',         [ExcelController::class, 'dumpable'])->name('dumpable');
});

Route::group(['prefix' => 'csv', 'as' => 'csv.'], function(){
    
});

Route::group(['prefix' => 'print', 'as' => 'print.'], function(){
    Route::get('customer_history', [PrintController::class, 'customerHistory'])->name('customer_history');
    Route::get('supplier_history', [PrintController::class, 'supplierHistory'])->name('supplier_history');
    Route::get('product_history', [PrintController::class, 'productHistory'])->name('product_history');
    Route::match(['get', 'post'], 'product_history_email', [PrintController::class, 'productHistoryEmail'])->name('product_history_email');
    Route::get('product_history_statement', [PrintController::class, 'productHistoryStatement'])->name('product_history_statement');
    Route::get('products',      [PrintController::class, 'products'])->name('products');
    Route::get('stock_closing', [PrintController::class, 'stockClosing'])->name('stock_closing');
    Route::get('unassigned_suppliers', [PrintController::class, 'unassignedSuppliers'])->name('unassigned_suppliers');
    Route::get('customer_return',     [PrintController::class, 'customerReturn'])->name('customer_return');
    Route::get('customer_returnable', [PrintController::class, 'customerReturnable'])->name('customer_returnable');
    Route::get('supplier_return',     [PrintController::class, 'supplierReturn'])->name('supplier_return');
    Route::get('supplier_returnable', [PrintController::class, 'supplierReturnable'])->name('supplier_returnable');
    Route::get('dump',                [PrintController::class, 'dump'])->name('dump');
    Route::get('dumpable',            [PrintController::class, 'dumpable'])->name('dumpable');
});

Route::group(['prefix' => 'whatsapp', 'as' => 'whatsapp.'], function(){
    
});

Route::group(['prefix' => 'email', 'as' => 'email.'], function(){
    
});

Route::group(['prefix' => 'sms', 'as' => 'sms.'], function(){
    
});

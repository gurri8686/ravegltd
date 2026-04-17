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
});

Route::group(['prefix' => 'csv', 'as' => 'csv.'], function(){
    
});

Route::group(['prefix' => 'print', 'as' => 'print.'], function(){
    Route::get('customer_history', [PrintController::class, 'customerHistory'])->name('customer_history');
    Route::get('supplier_history', [PrintController::class, 'supplierHistory'])->name('supplier_history');
    Route::get('product_history', [PrintController::class, 'productHistory'])->name('product_history');
    Route::get('product_history_email', [PrintController::class, 'productHistoryEmail'])->name('product_history_email');
    Route::get('product_history_statement', [PrintController::class, 'productHistoryStatement'])->name('product_history_statement');
});

Route::group(['prefix' => 'whatsapp', 'as' => 'whatsapp.'], function(){
    
});

Route::group(['prefix' => 'email', 'as' => 'email.'], function(){
    
});

Route::group(['prefix' => 'sms', 'as' => 'sms.'], function(){
    
});

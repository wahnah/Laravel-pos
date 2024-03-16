<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\ProductSnapshotController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect('/admin');
});

Auth::routes();

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class,'store'])->name('users.store');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');


    //Route::resource('orders', OrderController::class);
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orderscredlist', [OrderController::class, 'credlist'])->name('orders.credlist');
    Route::get('/ordersedit/{order_id}', [OrderController::class, 'edit'])->name('orders.edit');
    Route::post('/order/{orderItemId}', [OrderController::class, 'updateOrderItem'])->name('orderItem.update');
    Route::get('/creceipt/{order_id}', [OrderController::class, 'creceipt'])->name('orders.creceipt');
    Route::get('/oreceipt/{order_id}', [OrderController::class, 'oreceipt'])->name('orders.oreceipt');
    Route::get('/orders/ordered', [OrderController::class, 'getAllOrderItems'])->name('orders.getAllOrderItems');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orderscreateCust/{order_id}', [OrderController::class, 'createCust'])->name('orders.createCust');
    Route::post('/orderscustStore/{order_id}', [OrderController::class, 'custStore'])->name('orders.custStore');
    Route::post('/ordersupdate/{order_id}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('/ordersupdatee/{order_id}', [OrderController::class, 'updatee'])->name('orders.updatee');
    Route::get('/orders/report', [OrderController::class, 'report'])->name('orders.report');
    Route::post('/restock/{productId}/{quantityAdded}', [RestockController::class, 'restockProduct'])->name('restock.restockProduct');
    Route::get('/restock', [RestockController::class, 'index'])->name('restock.index');
    Route::get('/restock/report', [RestockController::class, 'report'])->name('restock.report');
    Route::get('/orders/receipt', [OrderController::class, 'receipt']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/create', [CategoryController::class, 'category'])->name('category.category');
    Route::post('/categories', [CategoryController::class, 'addCategory'])->name('category.addCategory');

    Route::get('/open-day', [ProductSnapshotController::class, 'openDay'])->name('open-day');
    Route::get('/close-day', [ProductSnapshotController::class, 'closeDay'])->name('close-day');
    Route::get('/populateStockSheet', [ProductSnapshotController::class, 'populateStockSheet'])->name('stock.populateStockSheet');
    Route::get('/populateStockSheetReport', [ProductSnapshotController::class, 'populateStockSheetReport'])->name('stock.populateStockSheetReport');
    Route::get('/dailyReport', [ProductSnapshotController::class, 'compareCloseQtyToPrevDay'])->name('stock.dailyReport');
    Route::post('/yesterday-money', [ProductSnapshotController::class, 'moneyinfo']);
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/change-qty', [CartController::class, 'changeQty']);
    Route::delete('/cart/delete', [CartController::class, 'delete']);
    Route::delete('/cart/empty', [CartController::class, 'empty']);
    Route::get('/cart/customer/{customer_id}', [CartController::class, 'getCartItemsByCustomerId']);
    Route::get('/cart/checkphysockcount', [CartController::class, 'dailyStockcount']);
    Route::post('/cart/physockcount', [CartController::class, 'storeDailyStockcount']);
    Route::get('/cart/check', [CartController::class, 'dailyStockcountprod']);

});

<?php


use Illuminate\Support\Facades\Route;

Route::post('/checkout/{productID}', [\Inaxo\LaravelStripeHandler\controllers\StripePaymentController::class, 'checkout'])->name('checkout');
Route::get('/payment-success', [\Inaxo\LaravelStripeHandler\controllers\StripePaymentController::class, 'success'])->name('payment-success');
Route::get('/payment-cancel', [\Inaxo\LaravelStripeHandler\controllers\StripePaymentController::class, 'cancel'])->name('payment-cancel');
Route::get('/getProducts', [\Inaxo\LaravelStripeHandler\controllers\StripePaymentController::class, 'getProductFromXMLFile'])->name('getProducts');
Route::get('/home',function (){
    return view('welcome');
})->name('home');


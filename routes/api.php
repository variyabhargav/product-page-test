<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('token', [ProductController::class, 'generateToken']);

// Route::group(["middleware" => ['jwt.verify']], function () {
    Route::get('products', [ProductController::class, 'getProducts']);
    Route::post('add-product', [ProductController::class, 'addProduct']);
    Route::post('edit-product', [ProductController::class, 'updateProduct']);
    Route::get('remove/{product}', [ProductController::class, 'removeProduct']);

    Route::post('update-image', [ProductController::class, 'updateImages']);

    Route::get('discounts', [ProductController::class, 'getDiscounts']);
    Route::post('discount', [ProductController::class, 'addDiscount']);
    Route::get('delete-discount/{discount}', [ProductController::class, 'deleteDiscount']);
// });

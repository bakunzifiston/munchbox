<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryController as AdminDeliveryController;
use App\Http\Controllers\Admin\IngredientController as AdminIngredientController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OptionGroupController as AdminOptionGroupController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MenuController::class, 'index'])->name('home');
Route::get('/menu', fn () => redirect()->route('home', request()->query()))->name('menu.index');
Route::get('/menu/{slug}', [MenuController::class, 'show'])->name('menu.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{key}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/orders/track', [OrderController::class, 'track'])->name('orders.track');
Route::get('/orders/{order}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');

    Route::get('/my-orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::resource('categories', AdminCategoryController::class)->except(['show', 'create', 'edit']);

    Route::get('/options', [AdminOptionGroupController::class, 'index'])->name('options.index');
    Route::post('/options', [AdminOptionGroupController::class, 'store'])->name('options.store');
    Route::get('/options/{option}', [AdminOptionGroupController::class, 'show'])->name('options.show');
    Route::put('/options/{option}', [AdminOptionGroupController::class, 'update'])->name('options.update');
    Route::delete('/options/{option}', [AdminOptionGroupController::class, 'destroy'])->name('options.destroy');
    Route::post('/options/{option}/choices', [AdminOptionGroupController::class, 'storeOption'])->name('options.choices.store');
    Route::put('/options/{option}/choices/{choice}', [AdminOptionGroupController::class, 'updateOption'])->name('options.choices.update');
    Route::delete('/options/{option}/choices/{choice}', [AdminOptionGroupController::class, 'destroyOption'])->name('options.choices.destroy');

    Route::get('/ingredients', [AdminIngredientController::class, 'index'])->name('ingredients.index');
    Route::post('/ingredients', [AdminIngredientController::class, 'store'])->name('ingredients.store');
    Route::patch('/ingredients/{ingredient}', [AdminIngredientController::class, 'update'])->name('ingredients.update');
    Route::delete('/ingredients/{ingredient}', [AdminIngredientController::class, 'destroy'])->name('ingredients.destroy');

    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{item}/movements', [AdminInventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('/inventory/{item}/adjust', [AdminInventoryController::class, 'adjust'])->name('inventory.adjust');

    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');

    Route::get('/deliveries', [AdminDeliveryController::class, 'index'])->name('deliveries.index');
    Route::patch('/deliveries/{delivery}', [AdminDeliveryController::class, 'update'])->name('deliveries.update');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::middleware('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    });
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user?->isStaff()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('menu.index');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

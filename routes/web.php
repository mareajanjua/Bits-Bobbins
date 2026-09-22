<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\StorefrontController;

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
    return redirect()->route('store.home');
});

Route::prefix('employee')->name('employee.')->middleware('employee.session')->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [EmployeeDashboardController::class, 'orders'])->name('orders');
    Route::get('/orders/{item}', [EmployeeDashboardController::class, 'showOrder'])->name('orders.show');
    Route::post('/orders/{item}', [EmployeeDashboardController::class, 'updateOrder'])->name('orders.update');
    Route::get('/delivery/{type}', [EmployeeDashboardController::class, 'deliveryQueue'])->whereIn('type', ['pending', 'dispatched', 'delivered'])->name('delivery');
    Route::get('/delivery-reports', [EmployeeDashboardController::class, 'reports'])->name('reports');
    Route::get('/account', [EmployeeDashboardController::class, 'account'])->name('account');
    Route::post('/account/profile', [EmployeeDashboardController::class, 'updateProfile'])->name('account.profile');
    Route::post('/account/password', [EmployeeDashboardController::class, 'updatePassword'])->name('account.password');
});

Route::get('/home', [StorefrontController::class, 'home'])->name('store.home');
Route::redirect('/shop', '/home');
Route::get('/about', [StorefrontController::class, 'about'])->name('store.about');
Route::get('/products', [StorefrontController::class, 'products'])->name('store.products');
Route::get('/products/{product}', [StorefrontController::class, 'product'])->name('store.product');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.index');
Route::post('/cart/{product}', [StorefrontController::class, 'addToCart'])->name('cart.add');
Route::post('/cart/{product}/update', [StorefrontController::class, 'updateCart'])->name('cart.update');
Route::get('/login', [StorefrontController::class, 'loginForm'])->name('customer.login');
Route::post('/login', [StorefrontController::class, 'login'])->name('customer.login.submit');
Route::get('/register', [StorefrontController::class, 'registerForm'])->name('customer.register');
Route::post('/register', [StorefrontController::class, 'register'])->name('customer.register.submit');
Route::get('/forgot-password', [StorefrontController::class, 'forgotPasswordForm'])->name('customer.password.request');
Route::post('/forgot-password', [StorefrontController::class, 'forgotPassword'])->name('customer.password.email');
Route::post('/logout', [StorefrontController::class, 'logout'])->name('customer.logout');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout/place-order', [StorefrontController::class, 'placeOrder'])->name('checkout.place');

Route::middleware('customer.session')->group(function () {
    Route::get('/account', [StorefrontController::class, 'account'])->name('customer.account');
    Route::post('/account/profile', [StorefrontController::class, 'updateCustomerProfile'])->name('customer.profile.update');
    Route::post('/account/password', [StorefrontController::class, 'updateCustomerPassword'])->name('customer.password.update');
    Route::get('/order-confirmation/{order}', [StorefrontController::class, 'confirmation'])->name('order.confirmation');
    Route::get('/my-orders', [StorefrontController::class, 'myOrders'])->name('customer.orders');
    Route::get('/my-orders/{order}', [StorefrontController::class, 'myOrderDetail'])->name('customer.orders.show');
    Route::get('/my-addresses', [StorefrontController::class, 'addresses'])->name('customer.addresses');
    Route::post('/my-addresses', [StorefrontController::class, 'saveAddress'])->name('customer.addresses.store');
    Route::get('/feedback', [StorefrontController::class, 'feedback'])->name('customer.feedback');
    Route::post('/feedback', [StorefrontController::class, 'submitFeedback'])->name('customer.feedback.store');
});

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware('admin.session')->name('admin.dashboard');

Route::prefix('admin')->name('admin.')->middleware('admin.session')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::post('/account', [AccountController::class, 'updateProfile'])->name('account.update');
    Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::get('/products', [ModuleController::class, 'products'])->name('products.index');
    Route::get('/product-details', [ModuleController::class, 'productDetails'])->name('product-details.index');
    Route::get('/products/create', [ModuleController::class, 'productForm'])->name('products.create');
    Route::post('/products', [ModuleController::class, 'saveProduct'])->name('products.store');
    Route::get('/products/{product}/edit', [ModuleController::class, 'productForm'])->name('products.edit');
    Route::post('/products/{product}', [ModuleController::class, 'saveProduct'])->name('products.update');
    Route::get('/categories', [ModuleController::class, 'categories'])->name('categories.index');
    Route::get('/categories/create', [ModuleController::class, 'categoryForm'])->name('categories.create');
    Route::post('/categories', [ModuleController::class, 'saveCategory'])->name('categories.store');
    Route::get('/categories/{category}/edit', [ModuleController::class, 'categoryForm'])->name('categories.edit');
    Route::post('/categories/{category}', [ModuleController::class, 'saveCategory'])->name('categories.update');
    Route::post('/categories/{category}/subcategories', [ModuleController::class, 'saveSubcategory'])->name('categories.subcategories.store');
    Route::delete('/subcategories/{subcategory}', [ModuleController::class, 'deleteSubcategory'])->name('subcategories.destroy');
    Route::get('/stock', fn (ModuleController $controller) => $controller->stock(false))->name('stock.index');
    Route::get('/stock/low', fn (ModuleController $controller) => $controller->stock(true))->name('stock.low');
    Route::get('/orders', [ModuleController::class, 'orders'])->name('orders.index');
    Route::get('/orders/delivery-type', [ModuleController::class, 'orders'])->name('orders.delivery');
    Route::get('/orders/{order}', [ModuleController::class, 'orderDetail'])->name('orders.show');
    Route::get('/employees', [ModuleController::class, 'employees'])->name('employees.index');
    Route::get('/employees/create', [ModuleController::class, 'employeeForm'])->name('employees.create');
    Route::post('/employees', [ModuleController::class, 'storeEmployee'])->name('employees.store');
    Route::post('/employees/{employee}/deactivate', [ModuleController::class, 'deactivateEmployee'])->name('employees.deactivate');
    Route::post('/employees/{employee}/activate', [ModuleController::class, 'activateEmployee'])->name('employees.activate');
    Route::get('/customers', fn (ModuleController $controller) => $controller->customers(false))->name('customers.index');
    Route::get('/customers/deactivated', fn (ModuleController $controller) => $controller->customers(true))->name('customers.deactivated');
    Route::get('/customers/{customer}', [ModuleController::class, 'customerDetail'])->name('customers.show');
    Route::get('/payments/{method}', [ModuleController::class, 'payments'])->whereIn('method', ['credit_card', 'cheque', 'vpp_cod', 'dd'])->name('payments.method');
    Route::post('/payments/{payment}/clear-cheque', [ModuleController::class, 'clearChequePayment'])->name('payments.cheque.clear');
    Route::get('/returns', [ModuleController::class, 'returns'])->name('returns.index');
    Route::post('/returns/{request}/approve', [ModuleController::class, 'approveReturn'])->name('returns.approve');
    Route::post('/returns/{request}/reject', [ModuleController::class, 'rejectReturn'])->name('returns.reject');
    Route::get('/warranty', [ModuleController::class, 'warranty'])->name('warranty.index');
    Route::get('/feedback', [ModuleController::class, 'feedback'])->name('feedback.index');
    Route::post('/feedback/{feedback}/reviewed', [ModuleController::class, 'markFeedbackReviewed'])->name('feedback.reviewed');
    Route::get('/faq', [ModuleController::class, 'faq'])->name('faq.index');
    Route::get('/faq/create', [ModuleController::class, 'faqForm'])->name('faq.create');
    Route::post('/faq', [ModuleController::class, 'saveFaq'])->name('faq.store');
    Route::get('/faq/{faq}/edit', [ModuleController::class, 'faqForm'])->name('faq.edit');
    Route::post('/faq/{faq}', [ModuleController::class, 'saveFaq'])->name('faq.update');
    Route::delete('/faq/{faq}', [ModuleController::class, 'deleteFaq'])->name('faq.destroy');
    Route::get('/settings/{page?}', [ModuleController::class, 'settings'])->name('settings');
});

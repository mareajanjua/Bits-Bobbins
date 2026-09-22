<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(['admin.*', 'employee.*'], function ($view) {
            $lowStockThreshold = 5;
            $notifications = collect([
                [
                    'label' => 'New orders waiting for review',
                    'count' => DB::table('orders')->where('order_status', 'placed')->count(),
                    'icon' => 'bi-bag-check',
                    'url' => route('admin.orders.index'),
                    'tone' => 'bg-primary text-white',
                ],
                [
                    'label' => 'Payments cleared',
                    'count' => DB::table('payment')->where('payment_status', 'cleared')->count(),
                    'icon' => 'bi-credit-card',
                    'url' => route('admin.payments.method', 'credit_card'),
                    'tone' => 'bg-success text-white',
                ],
                [
                    'label' => 'Failed payments need attention',
                    'count' => DB::table('payment')->where('payment_status', 'failed')->count(),
                    'icon' => 'bi-exclamation-triangle',
                    'url' => route('admin.payments.method', 'credit_card'),
                    'tone' => 'bg-danger text-white',
                ],
                [
                    'label' => 'Low-stock products',
                    'count' => DB::table('stock')->where('quantity_available', '>', 0)->where('quantity_available', '<=', $lowStockThreshold)->count(),
                    'icon' => 'bi-box-seam',
                    'url' => route('admin.stock.low'),
                    'tone' => 'bg-warning text-dark',
                ],
                [
                    'label' => 'Return or replacement requests',
                    'count' => DB::table('return_replace_request')->whereIn('status', ['requested', 'approved'])->count(),
                    'icon' => 'bi-arrow-counterclockwise',
                    'url' => route('admin.returns.index'),
                    'tone' => 'bg-info text-white',
                ],
                [
                    'label' => 'Customer feedback received',
                    'count' => DB::table('feedback')->count(),
                    'icon' => 'bi-chat-square-heart',
                    'url' => route('admin.feedback.index'),
                    'tone' => 'bg-secondary text-white',
                ],
            ])->filter(fn ($notification) => $notification['count'] > 0)->values();

            $currentUser = Admin::query()->first();

            if (session('employee_id')) {
                $employee = DB::table('employee')->where('employee_id', session('employee_id'))->first();
                if ($employee) {
                    $currentUser = (object) [
                        'username' => $employee->full_name,
                        'email' => $employee->email,
                        'profile_photo' => $employee->profile_photo ?? null,
                    ];
                }
            }

            $view->with([
                'currentAdmin' => $currentUser,
                'adminNotifications' => $notifications,
                'adminNotificationCount' => $notifications->sum('count'),
            ]);
        });
    }
}

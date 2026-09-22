<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $lowStockThreshold = 5;

        $ordersToday = DB::table('orders')->whereDate('order_date', $today)->count();
        $ordersThisWeek = DB::table('orders')->whereDate('order_date', '>=', $weekStart)->count();

        $ordersPendingDispatch = DB::table('orders')
            ->where('order_status', 'payment_cleared')
            ->count();

        $clearedRevenue = DB::table('payment')
            ->where('payment_status', 'cleared')
            ->whereDate('payment_date', '>=', $monthStart)
            ->sum('amount');

        $lowStockProducts = DB::table('stock')
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', $lowStockThreshold)
            ->count();

        $outOfStockProducts = DB::table('stock')->where('quantity_available', '<=', 0)->count();
        $totalActiveProducts = DB::table('product')->where('is_active', 1)->count();

        $statusLabels = ['placed', 'payment_pending', 'payment_cleared', 'dispatched', 'delivered', 'cancelled'];
        $statusCounts = DB::table('orders')
            ->select('order_status', DB::raw('COUNT(*) as total'))
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->toArray();

        $returnRequestedCount = DB::table('return_replace_request')
            ->where('status', 'requested')
            ->count();

        $ordersByStatus = [
            'labels' => ['Placed', 'Payment Pending', 'Payment Cleared', 'Dispatched', 'Delivered', 'Cancelled', 'Return Requested'],
            'values' => array_merge(
                array_map(fn ($status) => (int) ($statusCounts[$status] ?? 0), $statusLabels),
                [(int) $returnRequestedCount]
            ),
        ];

        $deliveryRows = DB::table('order_item')
            ->join('delivery_type', 'order_item.delivery_code', '=', 'delivery_type.delivery_code')
            ->select('delivery_type.delivery_name', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_type.delivery_name')
            ->orderBy('delivery_type.delivery_name')
            ->get();

        $ordersByDeliveryType = [
            'labels' => $deliveryRows->pluck('delivery_name')->values()->toArray(),
            'values' => $deliveryRows->pluck('total')->map(fn ($value) => (int) $value)->values()->toArray(),
        ];

        $paymentRows = DB::table('payment')
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        $paymentBreakdown = [
            'labels' => ['Credit Card', 'Cheque', 'VPP / Cash on Delivery'],
            'values' => [
                (int) ($paymentRows['credit_card'] ?? 0),
                (int) ($paymentRows['cheque'] ?? 0),
                (int) ($paymentRows['vpp_cod'] ?? 0),
            ],
        ];

        $recentOrders = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.order_id')
            ->join('customer', 'orders.customer_id', '=', 'customer.customer_id')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->leftJoin('payment', 'orders.order_id', '=', 'payment.order_id')
            ->select(
                'order_item.order_number',
                'customer.full_name as customer_name',
                'product.product_name',
                DB::raw('(order_item.quantity * order_item.unit_price) as total_amount'),
                DB::raw('COALESCE(payment.payment_status, "pending") as payment_status'),
                'orders.order_status',
                'orders.order_id',
                'orders.order_date'
            )
            ->orderByDesc('orders.order_date')
            ->limit(6)
            ->get();

        $topSellingProducts = DB::table('order_item')
            ->join('product', 'order_item.product_id', '=', 'product.product_id')
            ->select('product.product_name', DB::raw('SUM(order_item.quantity) as sold_quantity'))
            ->whereIn('order_item.item_status', ['delivered', 'replaced'])
            ->groupBy('product.product_id', 'product.product_name')
            ->orderByDesc('sold_quantity')
            ->limit(3)
            ->get();

        $pendingReturns = DB::table('return_replace_request')
            ->whereIn('status', ['requested', 'approved'])
            ->count();

        $recentFeedback = DB::table('feedback')
            ->join('customer', 'feedback.customer_id', '=', 'customer.customer_id')
            ->select('customer.full_name', 'feedback.rating', 'feedback.message', 'feedback.submitted_at')
            ->orderByDesc('feedback.submitted_at')
            ->limit(5)
            ->get();

        $attentionAlerts = collect([
            [
                'label' => 'Pending cheque clearances',
                'description' => 'Orders waiting for cheque payment confirmation',
                'count' => DB::table('payment')->where('payment_method', 'cheque')->where('payment_status', 'pending')->count(),
                'icon' => 'bi-bank',
                'tone' => 'blue',
            ],
            [
                'label' => 'Overdue dispatches',
                'description' => 'Cleared orders waiting for dispatch',
                'count' => $ordersPendingDispatch,
                'icon' => 'bi-truck',
                'tone' => 'orange',
            ],
            [
                'label' => 'Low stock products',
                'description' => 'Products are low or out of stock',
                'count' => $lowStockProducts + $outOfStockProducts,
                'icon' => 'bi-box-seam',
                'tone' => 'orange',
            ],
            [
                'label' => 'New return / replacement requests',
                'description' => 'Requests waiting for admin review',
                'count' => $pendingReturns,
                'icon' => 'bi-arrow-counterclockwise',
                'tone' => 'green',
            ],
            [
                'label' => 'Failed payments',
                'description' => 'Payments that need admin attention',
                'count' => DB::table('payment')->where('payment_status', 'failed')->count(),
                'icon' => 'bi-exclamation-triangle',
                'tone' => 'red',
            ],
            [
                'label' => 'New customer feedback',
                'description' => 'Feedback submitted today',
                'count' => DB::table('feedback')->whereDate('submitted_at', $today)->count(),
                'icon' => 'bi-chat-square-heart',
                'tone' => 'blue',
            ],
        ])->filter(fn ($alert) => $alert['count'] > 0)->values();

        return view('admin.dashboard', compact(
            'ordersToday',
            'ordersThisWeek',
            'ordersPendingDispatch',
            'clearedRevenue',
            'lowStockProducts',
            'outOfStockProducts',
            'totalActiveProducts',
            'ordersByStatus',
            'ordersByDeliveryType',
            'paymentBreakdown',
            'recentOrders',
            'topSellingProducts',
            'pendingReturns',
            'recentFeedback',
            'attentionAlerts',
            'lowStockThreshold'
        ));
    }
}

@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@php
    $hasStatusData = array_sum($ordersByStatus['values']) > 0;
    $hasDeliveryData = array_sum($ordersByDeliveryType['values']) > 0;
    $hasPaymentData = array_sum($paymentBreakdown['values']) > 0;

    $badgeClass = function ($status) {
        return match ($status) {
            'cleared', 'payment_cleared', 'delivered' => 'status-badge status-badge-green',
            'pending', 'payment_pending' => 'status-badge status-badge-amber',
            'failed', 'cancelled' => 'status-badge status-badge-red',
            'placed' => 'status-badge status-badge-blue',
            'dispatched' => 'status-badge status-badge-purple',
            'return_requested' => 'status-badge status-badge-orange',
            default => 'status-badge status-badge-gray',
        };
    };
@endphp

@section('content')
@php
  $lowStockTone = $lowStockProducts > 1 ? 'metric-red' : ($lowStockProducts === 1 ? 'metric-orange' : 'metric-green');
  $outOfStockTone = $outOfStockProducts > 0 ? 'metric-red' : 'metric-green';
@endphp
    <div class="page-header dashboard-overview-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back! Here's an overview of your store.</p>
      </div>
      <div class="dropdown">
        <button class="btn-date-picker dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-calendar4-event"></i>
          <span>This Week</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
          <li><a class="dropdown-item" href="#">Today</a></li>
          <li><a class="dropdown-item" href="#">This Week</a></li>
          <li><a class="dropdown-item" href="#">This Month</a></li>
        </ul>
      </div>
    </div>

    <div class="row g-3 dashboard-overview-grid">
      <div class="col-xl-3 col-md-6">
        <a href="{{ route('admin.orders.index') }}" class="dashboard-summary-card dashboard-summary-card-green">
          <div class="dashboard-summary-icon">
            <i class="bi bi-cart3"></i>
          </div>
          <div class="dashboard-summary-content">
            <div class="dashboard-summary-title">Orders Received</div>
            <div class="dashboard-summary-desc">Today / This Week</div>
            <div class="dashboard-summary-value">{{ $ordersToday > 0 ? $ordersToday : '-' }}</div>
          </div>
          <i class="bi bi-chevron-right dashboard-summary-chevron"></i>
        </a>
      </div>

      <div class="col-xl-3 col-md-6">
        <a href="{{ route('admin.orders.index', ['status' => 'payment_cleared']) }}" class="dashboard-summary-card dashboard-summary-card-red">
          <div class="dashboard-summary-icon">
            <i class="bi bi-truck"></i>
          </div>
          <div class="dashboard-summary-content">
            <div class="dashboard-summary-title">Pending Dispatch</div>
            <div class="dashboard-summary-desc">Payment cleared, not shipped</div>
            <div class="dashboard-summary-value">{{ $ordersPendingDispatch > 0 ? $ordersPendingDispatch : '-' }}</div>
          </div>
          <i class="bi bi-chevron-right dashboard-summary-chevron"></i>
        </a>
      </div>

      <div class="col-xl-3 col-md-6">
        <a href="{{ route('admin.payments.method', 'credit_card') }}" class="dashboard-summary-card dashboard-summary-card-green">
          <div class="dashboard-summary-icon">
            <i class="bi bi-coin"></i>
          </div>
          <div class="dashboard-summary-content">
            <div class="dashboard-summary-title">Cleared Payment Revenue</div>
            <div class="dashboard-summary-desc">This period</div>
            <div class="dashboard-summary-value">{{ $clearedRevenue > 0 ? 'PKR ' . number_format($clearedRevenue, 2) : '-' }}</div>
          </div>
          <i class="bi bi-chevron-right dashboard-summary-chevron"></i>
        </a>
      </div>

      <div class="col-xl-3 col-md-6">
        <a href="{{ route('admin.stock.low') }}" class="dashboard-summary-card dashboard-summary-card-orange">
          <div class="dashboard-summary-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div class="dashboard-summary-content">
            <div class="dashboard-summary-title">Low Stock Alerts</div>
            <div class="dashboard-summary-desc">Products below threshold</div>
            <div class="dashboard-summary-value">{{ $lowStockProducts > 0 ? $lowStockProducts : '-' }}</div>
          </div>
          <i class="bi bi-chevron-right dashboard-summary-chevron"></i>
        </a>
      </div>

      <div class="col-xl-4 col-lg-6">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-bar-chart"></i> Orders by Status</h2>
            <button class="dashboard-period-btn" type="button">This Month <i class="bi bi-chevron-down"></i></button>
          </div>
          @if ($hasStatusData)
            <div id="orders-status-chart" class="dashboard-chart"></div>
          @else
            <div class="dashboard-empty-state">
              <div class="dashboard-empty-icon"><i class="bi bi-bar-chart"></i></div>
              <strong>No data available yet</strong>
              <span>Orders by status will appear here once there is data.</span>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-4 col-lg-6">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-truck"></i> Orders by Delivery Type</h2>
            <button class="dashboard-period-btn" type="button">This Month <i class="bi bi-chevron-down"></i></button>
          </div>
          @if ($hasDeliveryData)
            <div id="orders-delivery-chart" class="dashboard-chart"></div>
          @else
            <div class="dashboard-empty-state">
              <div class="dashboard-empty-icon dashboard-empty-donut"></div>
              <strong>No data available yet</strong>
              <span>Delivery type breakdown will appear here once there is data.</span>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-4 col-lg-12">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-credit-card"></i> Payment Method Breakdown</h2>
            <button class="dashboard-period-btn" type="button">This Month <i class="bi bi-chevron-down"></i></button>
          </div>
          @if ($hasPaymentData)
            <div id="payment-method-chart" class="dashboard-chart"></div>
          @else
            <div class="dashboard-empty-state">
              <div class="dashboard-empty-icon dashboard-empty-donut"></div>
              <strong>No data available yet</strong>
              <span>Payment method breakdown will appear here once there is data.</span>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-receipt"></i> Recent Orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="dashboard-link">View All</a>
          </div>
          @if ($recentOrders->isNotEmpty())
            <div class="table-responsive recent-orders-scroll">
              <table class="table table-custom dashboard-table recent-orders-table mb-0">
                <thead>
                  <tr>
                    <th>Order No.</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($recentOrders as $order)
                    <tr>
                      <td class="table-order-id">{{ $order->order_number ?? '-' }}</td>
                      <td>{{ $order->customer_name }}</td>
                      <td>{{ $order->product_name }}</td>
                      <td class="table-amount">PKR {{ number_format($order->total_amount, 2) }}</td>
                      <td><span class="{{ $badgeClass($order->payment_status) }}">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span></td>
                      <td><span class="{{ $badgeClass($order->order_status) }}">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span></td>
                      <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</td>
                      <td><a href="{{ route('admin.orders.show', $order->order_id) }}" class="table-btn-action" title="View"><i class="bi bi-eye"></i></a></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="dashboard-empty-state dashboard-empty-state-table">
              <div class="dashboard-empty-icon"><i class="bi bi-receipt"></i></div>
              <strong>No orders found</strong>
              <span>Orders will appear here once customers place them.</span>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-box-seam"></i> Inventory Snapshot</h2>
          </div>
          <div class="dashboard-metric-list">
            <div class="dashboard-metric-row metric-green">
              <div class="dashboard-metric-title">
                <span class="dashboard-metric-icon"><i class="bi bi-box-seam"></i></span>
                <span class="dashboard-metric-label">Total Active Products</span>
              </div>
              <div class="dashboard-metric-bottom">
                <span class="dashboard-metric-track"></span>
                <strong>{{ $totalActiveProducts > 0 ? $totalActiveProducts : '-' }}</strong>
              </div>
            </div>
            <div class="dashboard-metric-row {{ $lowStockTone }}">
              <div class="dashboard-metric-title">
                <span class="dashboard-metric-icon"><i class="bi bi-exclamation-triangle"></i></span>
                <span class="dashboard-metric-label">Low Stock Products</span>
              </div>
              <div class="dashboard-metric-bottom">
                <span class="dashboard-metric-track"></span>
                <strong>{{ $lowStockProducts > 0 ? $lowStockProducts : '-' }}</strong>
              </div>
            </div>
            <div class="dashboard-metric-row {{ $outOfStockTone }}">
              <div class="dashboard-metric-title">
                <span class="dashboard-metric-icon"><i class="bi bi-x-circle"></i></span>
                <span class="dashboard-metric-label">Out of Stock Products</span>
              </div>
              <div class="dashboard-metric-bottom">
                <span class="dashboard-metric-track"></span>
                <strong>{{ $outOfStockProducts > 0 ? $outOfStockProducts : '-' }}</strong>
              </div>
            </div>
            <div class="dashboard-metric-row metric-purple">
              <div class="dashboard-metric-title">
                <span class="dashboard-metric-icon"><i class="bi bi-graph-up-arrow"></i></span>
                <span class="dashboard-metric-label">Top-Selling Products</span>
              </div>
              <div class="dashboard-metric-bottom">
                <span class="dashboard-metric-track"></span>
                <strong>{{ $topSellingProducts->isNotEmpty() ? $topSellingProducts->count() : '-' }}</strong>
              </div>
            </div>
          </div>
          @if ($topSellingProducts->isNotEmpty())
            <div class="dashboard-mini-list">
              @foreach ($topSellingProducts as $product)
                <div><span>{{ $product->product_name }}</span><strong>{{ $product->sold_quantity }}</strong></div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-4 col-md-6">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-arrow-counterclockwise"></i> Pending Returns & Replacements</h2>
            <a href="{{ route('admin.returns.index') }}" class="dashboard-link">View All</a>
          </div>
          @if ($pendingReturns > 0)
            <div class="dashboard-large-number">{{ $pendingReturns }}</div>
            <p class="dashboard-muted">Return or replacement requests waiting for approval.</p>
          @else
            <div class="dashboard-empty-state">
              <div class="dashboard-empty-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
              <strong>No pending requests</strong>
              <span>Return or replacement requests will appear here.</span>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-4 col-md-6">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-chat-square-heart"></i> Recent Feedback</h2>
            <a href="{{ route('admin.feedback.index') }}" class="dashboard-link">View All</a>
          </div>
          @if ($recentFeedback->isNotEmpty())
            <div class="dashboard-feedback-list">
              @foreach ($recentFeedback as $feedback)
                <div class="dashboard-feedback-item">
                  <strong>{{ $feedback->full_name }}</strong>
                  <span>Rating {{ $feedback->rating ?? 'N/A' }}/5</span>
                  <p>{{ \Illuminate\Support\Str::limit($feedback->message, 76) }}</p>
                </div>
              @endforeach
            </div>
          @else
            <div class="dashboard-empty-state">
              <div class="dashboard-empty-icon"><i class="bi bi-chat-square-heart"></i></div>
              <strong>No feedback yet</strong>
              <span>Customer feedback will appear here.</span>
            </div>
          @endif
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card dashboard-overview-card h-100">
          <div class="dashboard-card-header">
            <h2 class="card-title"><i class="bi bi-megaphone"></i> Admin Alerts & Announcements</h2>
          </div>
          @if ($attentionAlerts->isNotEmpty())
            <div class="dashboard-alert-list">
              @foreach ($attentionAlerts as $alert)
                <a href="#" class="dashboard-alert-row alert-{{ $alert['tone'] }}">
                  <span class="dashboard-alert-icon"><i class="bi {{ $alert['icon'] }}"></i></span>
                  <span class="dashboard-alert-content">
                    <strong>{{ $alert['label'] }}</strong>
                    <small>{{ $alert['description'] }}</small>
                  </span>
                  <span class="dashboard-alert-track"></span>
                  <span class="dashboard-alert-count">{{ $alert['count'] > 0 ? $alert['count'] : '-' }}</span>
                  <i class="bi bi-chevron-right dashboard-summary-chevron"></i>
                </a>
              @endforeach
            </div>
          @else
            <div class="dashboard-empty-state">
              <div class="dashboard-empty-icon"><i class="bi bi-megaphone"></i></div>
              <strong>No alerts requiring attention.</strong>
            </div>
          @endif
        </div>
      </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ordersByStatus = @json($ordersByStatus);
    const ordersByDeliveryType = @json($ordersByDeliveryType);
    const paymentBreakdown = @json($paymentBreakdown);
    const chartFont = 'Plus Jakarta Sans, sans-serif';

    if (document.querySelector('#orders-status-chart')) {
        new ApexCharts(document.querySelector('#orders-status-chart'), {
            series: [{ name: 'Orders', data: ordersByStatus.values }],
            chart: { type: 'bar', height: 230, toolbar: { show: false }, fontFamily: chartFont },
            colors: ['#5E442B'],
            plotOptions: { bar: { borderRadius: 5, columnWidth: '42%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: ordersByStatus.labels },
            yaxis: { allowDecimals: false },
            grid: { borderColor: '#E9EFEF', strokeDashArray: 4 }
        }).render();
    }

    if (document.querySelector('#orders-delivery-chart')) {
        new ApexCharts(document.querySelector('#orders-delivery-chart'), {
            series: ordersByDeliveryType.values,
            labels: ordersByDeliveryType.labels,
            chart: { type: 'donut', height: 230, fontFamily: chartFont },
            colors: ['#5E442B', '#A17B56', '#F97316', '#D8C3A5'],
            dataLabels: { enabled: false },
            legend: { position: 'bottom' }
        }).render();
    }

    if (document.querySelector('#payment-method-chart')) {
        new ApexCharts(document.querySelector('#payment-method-chart'), {
            series: paymentBreakdown.values,
            labels: paymentBreakdown.labels,
            chart: { type: 'donut', height: 230, fontFamily: chartFont },
            colors: ['#5E442B', '#A17B56', '#F97316'],
            dataLabels: { enabled: false },
            legend: { position: 'bottom' }
        }).render();
    }
});
</script>
@endsection

@if ($orders->isNotEmpty())
  <div class="table-responsive recent-orders-scroll">
    <table class="table table-custom dashboard-table recent-orders-table mb-0">
      <thead><tr><th>Order Number</th><th>Date Placed</th><th>Customer</th><th>Product</th><th>Delivery Type</th><th>Payment Status</th><th>Order Status</th><th>Action</th></tr></thead>
      <tbody>
        @foreach ($orders as $order)
          <tr>
            <td class="table-order-id">{{ $order->order_number ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</td>
            <td>{{ $order->full_name }}</td>
            <td>{{ $order->product_name }}</td>
            <td>{{ $order->delivery_name }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $order->item_status)) }}</td>
            <td><a class="table-btn-action" href="{{ route('employee.orders.show', $order->order_item_id) }}"><i class="bi bi-eye"></i></a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <div class="dashboard-empty-state dashboard-empty-state-table">
    <div class="dashboard-empty-icon"><i class="bi bi-clipboard-check"></i></div>
    <strong>No orders match this filter</strong>
  </div>
@endif

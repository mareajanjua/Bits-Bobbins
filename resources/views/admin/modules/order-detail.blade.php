@extends('layouts.admin')

@section('title', 'Order Detail')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-clipboard-check"></i> Order #{{ $header->order_id }}</h1>
    <p class="page-subtitle">{{ $header->full_name }} - {{ ucwords(str_replace('_', ' ', $header->order_status)) }}</p>
  </div>
  <a href="{{ route('admin.orders.index') }}" class="btn-custom btn-custom-light"><i class="bi bi-arrow-left"></i>Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card dashboard-overview-card mb-3">
      <div class="dashboard-card-header"><h2 class="card-title">Line Items</h2></div>
      <div class="table-responsive recent-orders-scroll">
        <table class="table table-custom dashboard-table recent-orders-table mb-0">
          <thead><tr><th>Order No.</th><th>Product</th><th>Delivery</th><th>Qty</th><th>Unit Price</th><th>Status</th></tr></thead>
          <tbody>
            @foreach ($items as $item)
              <tr>
                <td>{{ $item->order_number }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->delivery_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>PKR {{ number_format($item->unit_price, 2) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $item->item_status)) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card dashboard-overview-card">
      <div class="dashboard-card-header"><h2 class="card-title">Dispatch / Tracking Info</h2></div>
      @forelse ($dispatch as $row)
        <div class="dashboard-metric-row metric-orange mb-2">
          <span class="dashboard-metric-icon"><i class="bi bi-truck"></i></span>
          <span class="dashboard-metric-label">Tracking: {{ $row->courier_tracking_number ?? '-' }}</span>
          <span class="dashboard-metric-track"></span>
          <strong>{{ $row->actual_delivery_date ?? $row->expected_delivery_date ?? '-' }}</strong>
        </div>
      @empty
        <div class="dashboard-empty-state"><div class="dashboard-empty-icon"><i class="bi bi-truck"></i></div><strong>No dispatch record yet.</strong></div>
      @endforelse
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card dashboard-overview-card mb-3">
      <div class="dashboard-card-header"><h2 class="card-title">Payment Info</h2></div>
      @if ($payment)
        <p><strong>Method:</strong> {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($payment->payment_status) }}</p>
        <p><strong>Amount:</strong> PKR {{ number_format($payment->amount, 2) }}</p>
      @else
        <p class="dashboard-muted">No payment record yet.</p>
      @endif
    </div>
    <div class="card dashboard-overview-card">
      <div class="dashboard-card-header"><h2 class="card-title">Shipping Address</h2></div>
      <p class="mb-1"><strong>{{ $header->full_name }}</strong></p>
      <p class="mb-1">{{ $header->email }}</p>
      <p class="mb-1">{{ $header->phone ?? '-' }}</p>
      <p class="mb-0">{{ $header->address_line1 }} {{ $header->address_line2 }}<br>{{ $header->city }}, {{ $header->state }} {{ $header->postal_code }}</p>
    </div>
  </div>
</div>
@endsection

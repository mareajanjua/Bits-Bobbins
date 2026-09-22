@extends('layouts.admin')

@section('title', 'Delivery Reports')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-file-earmark-bar-graph"></i> Delivery Reports</h1>
    <p class="page-subtitle">Live delivery performance for the selected period.</p>
  </div>
</div>
@include('employee.partials.filters', ['action' => route('employee.reports'), 'deliveryTypes' => $deliveryTypes])
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="dashboard-summary-card dashboard-summary-card-green"><div class="dashboard-summary-icon"><i class="bi bi-send-check"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Orders Dispatched</div><div class="dashboard-summary-desc">Selected period</div><div class="dashboard-summary-value">{{ $totals['dispatched'] ?: '-' }}</div></div></div></div>
  <div class="col-md-4"><div class="dashboard-summary-card dashboard-summary-card-green"><div class="dashboard-summary-icon"><i class="bi bi-check2-circle"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Orders Delivered</div><div class="dashboard-summary-desc">Selected period</div><div class="dashboard-summary-value">{{ $totals['delivered'] ?: '-' }}</div></div></div></div>
  <div class="col-md-4"><div class="dashboard-summary-card dashboard-summary-card-orange"><div class="dashboard-summary-icon"><i class="bi bi-clock-history"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Delayed / Overdue</div><div class="dashboard-summary-desc">Past expected date</div><div class="dashboard-summary-value">{{ $totals['overdue'] ?: '-' }}</div></div></div></div>
</div>
<div class="card dashboard-overview-card">
  @if ($records->isNotEmpty())
    <div class="table-responsive recent-orders-scroll"><table class="table table-custom dashboard-table recent-orders-table mb-0">
      <thead><tr><th>Order Number</th><th>Dispatch Date</th><th>Expected Delivery</th><th>Actual Delivery</th><th>Status</th><th>Tracking</th></tr></thead>
      <tbody>
        @foreach ($records as $row)
          <tr><td>{{ $row->order_number }}</td><td>{{ $row->dispatch_date ? \Carbon\Carbon::parse($row->dispatch_date)->format('d M Y') : '-' }}</td><td>{{ $row->expected_delivery_date ?? '-' }}</td><td>{{ $row->actual_delivery_date ?? '-' }}</td><td>{{ ucwords(str_replace('_', ' ', $row->item_status)) }}</td><td>{{ $row->courier_tracking_number ?? '-' }}</td></tr>
        @endforeach
      </tbody>
    </table></div>
  @else
    <div class="dashboard-empty-state dashboard-empty-state-table"><div class="dashboard-empty-icon"><i class="bi bi-file-earmark-bar-graph"></i></div><strong>No delivery records match this filter</strong></div>
  @endif
</div>
@endsection

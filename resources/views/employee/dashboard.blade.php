@extends('layouts.admin')

@section('title', 'Employee Dashboard')

@section('content')
<div class="page-header dashboard-overview-header">
  <div>
    <h1 class="page-title">Employee Dashboard</h1>
    <p class="page-subtitle">Daily orders, dispatch, delivery status, and order queue.</p>
  </div>
</div>

@include('employee.partials.filters', ['action' => route('employee.dashboard'), 'deliveryTypes' => $deliveryTypes])

<div class="row g-3 dashboard-overview-grid">
  <div class="col-xl col-md-6"><a href="{{ route('employee.orders') }}" class="dashboard-summary-card dashboard-summary-card-green"><div class="dashboard-summary-icon"><i class="bi bi-bag-check"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Orders Received Today</div><div class="dashboard-summary-desc">New order records</div><div class="dashboard-summary-value">{{ $ordersReceivedToday ?: '-' }}</div></div><i class="bi bi-chevron-right dashboard-summary-chevron"></i></a></div>
  <div class="col-xl col-md-6"><a href="{{ route('employee.delivery', 'pending') }}" class="dashboard-summary-card dashboard-summary-card-red"><div class="dashboard-summary-icon"><i class="bi bi-truck"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Pending Dispatch</div><div class="dashboard-summary-desc">Payment cleared or VPP</div><div class="dashboard-summary-value">{{ $pendingDispatch ?: '-' }}</div></div><i class="bi bi-chevron-right dashboard-summary-chevron"></i></a></div>
  <div class="col-xl col-md-6"><a href="{{ route('employee.delivery', 'dispatched') }}" class="dashboard-summary-card dashboard-summary-card-green"><div class="dashboard-summary-icon"><i class="bi bi-send-check"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Dispatched Today</div><div class="dashboard-summary-desc">Courier records made</div><div class="dashboard-summary-value">{{ $dispatchedToday ?: '-' }}</div></div><i class="bi bi-chevron-right dashboard-summary-chevron"></i></a></div>
  <div class="col-xl col-md-6"><a href="{{ route('employee.delivery', 'delivered') }}" class="dashboard-summary-card dashboard-summary-card-green"><div class="dashboard-summary-icon"><i class="bi bi-check2-circle"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Delivered This Week</div><div class="dashboard-summary-desc">Completed deliveries</div><div class="dashboard-summary-value">{{ $deliveredThisWeek ?: '-' }}</div></div><i class="bi bi-chevron-right dashboard-summary-chevron"></i></a></div>
  <div class="col-xl col-md-6"><a href="{{ route('employee.reports') }}" class="dashboard-summary-card dashboard-summary-card-orange"><div class="dashboard-summary-icon"><i class="bi bi-exclamation-triangle"></i></div><div class="dashboard-summary-content"><div class="dashboard-summary-title">Overdue Dispatch</div><div class="dashboard-summary-desc">Older than 2 days</div><div class="dashboard-summary-value">{{ $overdueDispatch ?: '-' }}</div></div><i class="bi bi-chevron-right dashboard-summary-chevron"></i></a></div>

  <div class="col-12">
    <div class="card dashboard-overview-card">
      <div class="dashboard-card-header"><h2 class="card-title"><i class="bi bi-list-check"></i> Live Orders Queue</h2><a class="dashboard-link" href="{{ route('employee.orders') }}">View All</a></div>
      @include('employee.partials.orders-table', ['orders' => $orders])
    </div>
  </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Employee Orders')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-clipboard-check"></i> {{ $pageTitle ?? 'Orders' }}</h1>
    <p class="page-subtitle">{{ $pageSubtitle ?? 'View and filter live orders by date, delivery type, status, order number, or customer name.' }}</p>
  </div>
</div>
@include('employee.partials.filters', ['action' => route('employee.orders'), 'deliveryTypes' => $deliveryTypes])
<div class="card dashboard-overview-card">
  @include('employee.partials.orders-table', ['orders' => $orders])
  @if (method_exists($orders, 'links'))
    <div class="mt-3">{{ $orders->links() }}</div>
  @endif
</div>
@endsection

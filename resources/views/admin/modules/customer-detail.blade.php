@extends('layouts.admin')

@section('title', 'Customer Detail')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-people"></i> {{ $record->full_name }}</h1>
    <p class="page-subtitle">Read-only customer profile for support.</p>
  </div>
  <button class="btn-custom {{ $record->status === 'active' ? 'btn-custom-danger' : 'btn-custom-primary' }}">{{ $record->status === 'active' ? 'Deactivate Account' : 'Reactivate Account' }}</button>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card dashboard-overview-card h-100">
      <h2 class="card-title mb-3">Customer Information</h2>
      <p><strong>Email:</strong> {{ $record->email }}</p>
      <p><strong>Phone:</strong> {{ $record->phone ?? '-' }}</p>
      <p><strong>Status:</strong> {{ ucfirst($record->status) }}</p>
      <p><strong>Registered:</strong> {{ date('d M Y', strtotime($record->registered_at)) }}</p>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card dashboard-overview-card mb-3">
      <h2 class="card-title mb-3">Saved Addresses</h2>
      @forelse ($addresses as $address)
        <p>{{ $address->address_line1 }} {{ $address->address_line2 }}, {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
      @empty
        <p class="dashboard-muted">No saved addresses.</p>
      @endforelse
    </div>
    <div class="card dashboard-overview-card">
      <h2 class="card-title mb-3">Order History</h2>
      @forelse ($orders as $order)
        <div class="dashboard-metric-row metric-green mb-2">
          <span class="dashboard-metric-icon"><i class="bi bi-receipt"></i></span>
          <span class="dashboard-metric-label">Order #{{ $order->order_id }}</span>
          <span class="dashboard-metric-track"></span>
          <strong>{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</strong>
        </div>
      @empty
        <p class="dashboard-muted">No orders found.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection

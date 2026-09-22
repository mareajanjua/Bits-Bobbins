@extends('layouts.admin')

@section('title', 'Employee Order Detail')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-receipt"></i> Order Detail</h1>
    <p class="page-subtitle">{{ $record->order_number ?? '-' }}</p>
  </div>
  <a href="{{ route('employee.orders') }}" class="btn-custom btn-custom-light">Back to Orders</a>
</div>

@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="row g-3">
  <div class="col-xl-7">
    <div class="card dashboard-overview-card h-100">
      <h2 class="card-title"><i class="bi bi-person-lines-fill"></i> Customer & Order</h2>
      <div class="row g-3 mt-1">
        <div class="col-md-6"><strong>Customer</strong><p>{{ $record->full_name }}<br>{{ $record->email }}</p></div>
        <div class="col-md-6"><strong>Shipping Address</strong><p>{{ $address->address_line1 }} {{ $address->address_line2 }}<br>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p></div>
        <div class="col-md-6"><strong>Product</strong><p>{{ $record->product_name }} x {{ $record->quantity }}</p></div>
        <div class="col-md-6"><strong>Delivery Type</strong><p>{{ $record->delivery_code }} - {{ $record->delivery_name }}</p></div>
        <div class="col-md-6"><strong>Payment Status</strong><p>{{ ucwords(str_replace('_', ' ', $record->payment_status)) }} (view only)</p></div>
        <div class="col-md-6"><strong>Order Status</strong><p>{{ ucwords(str_replace('_', ' ', $record->item_status)) }}</p></div>
        <div class="col-md-6"><strong>Tracking Number</strong><p>{{ $record->courier_tracking_number ?? '-' }}</p></div>
        <div class="col-md-6"><strong>Dispatch Date</strong><p>{{ $record->dispatch_date ? \Carbon\Carbon::parse($record->dispatch_date)->format('d M Y') : '-' }}</p></div>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card dashboard-overview-card h-100">
      <h2 class="card-title"><i class="bi bi-truck"></i> Update Delivery</h2>
      <p class="page-subtitle">Credit card and cheque orders must have cleared payment before dispatch. VPP can be dispatched directly.</p>
      <form method="POST" action="{{ route('employee.orders.update', $record->order_item_id) }}" class="row g-3">
        @csrf
        <input type="hidden" name="current_item_status" value="{{ $record->item_status }}">
        <div class="col-12">
          <label class="form-label-custom">New Status</label>
          <select name="status" class="form-select-custom" required>
            <option value="dispatched">Mark Dispatched / In Transit</option>
            <option value="delivered">Mark Delivered</option>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label-custom">Dispatch Date</label><input type="date" name="dispatch_date" class="form-control-custom" value="{{ $record->dispatch_date ? \Carbon\Carbon::parse($record->dispatch_date)->format('Y-m-d') : now()->toDateString() }}"></div>
        <div class="col-md-6"><label class="form-label-custom">Expected Delivery</label><input type="date" name="expected_delivery_date" class="form-control-custom" value="{{ $record->expected_delivery_date ?? now()->addDays(3)->toDateString() }}"></div>
        <div class="col-md-6"><label class="form-label-custom">Actual Delivery</label><input type="date" name="actual_delivery_date" class="form-control-custom" value="{{ $record->actual_delivery_date }}"></div>
        <div class="col-md-6"><label class="form-label-custom">Tracking Number</label><input name="courier_tracking_number" class="form-control-custom" value="{{ $record->courier_tracking_number }}"></div>
        <div class="col-12"><button class="btn-custom btn-custom-primary" type="submit">Save Delivery Update</button></div>
      </form>
    </div>
  </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Order Confirmation')

@php
  $paymentLabel = $payment ? ucwords(str_replace('_', ' ', $payment->payment_status)) : 'Pending';
  $dispatchLabel = $dispatch->isNotEmpty() ? 'Dispatch record available' : 'Not dispatched yet';
@endphp

@section('content')
<section class="bb-order-success-page">
  <div class="bb-order-success-card">
    <div class="bb-order-success-icon"><i class="bi bi-check2"></i></div>
    <p class="bb-order-success-kicker">Order Success</p>
    <h1>Order Placed</h1>
    <p>Your order has been received. Keep these 16-digit order numbers for tracking.</p>

    <div class="bb-order-success-meta">
      <div>
        <span>Payment Status</span>
        <strong>{{ $paymentLabel }}</strong>
      </div>
      <div>
        <span>Dispatch Status</span>
        <strong>{{ $dispatchLabel }}</strong>
      </div>
    </div>

    <div class="bb-order-number-list">
      @foreach ($items as $item)
        <article>
          <span>{{ $item->product_name }}</span>
          <strong>{{ $item->order_number }}</strong>
        </article>
      @endforeach
    </div>

    <a class="bb-order-success-button" href="{{ route('customer.orders') }}"><i class="bi bi-bag-check"></i> My Orders</a>
  </div>
</section>
@endsection

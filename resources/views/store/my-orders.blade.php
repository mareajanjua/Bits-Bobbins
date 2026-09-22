@extends('layouts.app')

@section('title', 'My Orders')

@section('content')
<div class="store-page-head"><h1>My Orders</h1><p>Track order, payment, dispatch, warranty, and return/replace eligibility.</p></div>
<div class="store-panel">
  @forelse ($orders as $order)
    <a class="store-order-row" href="{{ route('customer.orders.show', $order->order_id) }}">
      <span>#{{ $order->order_id }}</span><strong>{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</strong><span>{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</span><i class="bi bi-chevron-right"></i>
    </a>
  @empty
    <p>No orders yet.</p>
  @endforelse
</div>
@endsection

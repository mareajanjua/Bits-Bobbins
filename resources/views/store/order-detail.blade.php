@extends('layouts.app')

@section('title', 'Order Detail')

@section('content')
<div class="store-page-head"><h1>Order #{{ $header->order_id }}</h1><p>Status: {{ ucwords(str_replace('_', ' ', $header->order_status)) }}</p></div>
<div class="store-panel">
  @if ($errors->any())
    <div class="store-error">{{ $errors->first() }}</div>
  @endif
  @if (session('status'))
    <div class="store-status">{{ session('status') }}</div>
  @endif
  @foreach ($items as $item)
    @php
      $dispatchRecord = $dispatch->firstWhere('order_item_id', $item->order_item_id);
      $actualDeliveryDate = $dispatchRecord?->actual_delivery_date;
      $withinReturnWindow = $item->item_status === 'delivered'
          && $actualDeliveryDate
          && \Carbon\Carbon::parse($actualDeliveryDate)->startOfDay()->diffInDays(now()->startOfDay(), false) <= 7;
      $requestRecord = $returnRequests->get($item->order_item_id);
      $warrantyCard = $warrantyCards->get($item->order_item_id);
    @endphp
    <div class="store-order-detail-row">
      <div><strong>{{ $item->product_name }}</strong><span>{{ $item->order_number }}</span></div>
      <div>Qty {{ $item->quantity }} | PKR {{ number_format($item->unit_price, 2) }}</div>
      <div>{{ ucwords(str_replace('_', ' ', $item->item_status)) }}</div>
      @if ($warrantyCard)
        <div><i class="bi bi-shield-check"></i> Warranty card: {{ \Carbon\Carbon::parse($warrantyCard->warranty_start_date)->format('d M Y') }} to {{ \Carbon\Carbon::parse($warrantyCard->warranty_end_date)->format('d M Y') }}</div>
      @elseif ($item->has_warranty)
        <div><i class="bi bi-shield-check"></i> Warranty {{ $item->warranty_months }} months, issued after delivery</div>
      @endif
      @if (! in_array($item->item_status, ['dispatched', 'delivered', 'cancelled', 'return_requested', 'returned', 'replace_requested', 'replaced']))
        <form method="POST" action="{{ route('customer.orders.items.cancel', [$header->order_id, $item->order_item_id]) }}">
          @csrf
          <button>Cancel Order</button>
        </form>
      @endif
      @if ($requestRecord)
        <div>{{ ucfirst($requestRecord->request_type) }} request: {{ ucfirst($requestRecord->status) }}</div>
      @elseif ($withinReturnWindow)
        <form method="POST" action="{{ route('customer.orders.items.return_replace', [$header->order_id, $item->order_item_id]) }}">
          @csrf
          <input type="hidden" name="request_type" value="return">
          <input type="hidden" name="reason" value="Customer requested return from order detail page.">
          <button>Return</button>
        </form>
        <form method="POST" action="{{ route('customer.orders.items.return_replace', [$header->order_id, $item->order_item_id]) }}">
          @csrf
          <input type="hidden" name="request_type" value="replace">
          <input type="hidden" name="reason" value="Customer requested replacement from order detail page.">
          <button>Replace</button>
        </form>
      @elseif ($item->item_status === 'delivered')
        <div>Return/replace window closed.</div>
      @endif
    </div>
  @endforeach
  <p><strong>Payment:</strong> {{ $payment ? ucwords(str_replace('_', ' ', $payment->payment_status)) : 'Pending' }}</p>
  <p><strong>Dispatch:</strong> {{ $dispatch->isNotEmpty() ? 'Dispatch record available' : 'Not dispatched yet' }}</p>
  @if ($dispatch->isNotEmpty())
    @foreach ($dispatch as $record)
      <div class="store-review-row">
        <span>Tracking: {{ $record->courier_tracking_number ?? '-' }}</span>
        <strong>{{ $record->dispatch_date ? \Carbon\Carbon::parse($record->dispatch_date)->format('d M Y') : 'No dispatch date' }}</strong>
      </div>
      <div class="store-review-row">
        <span>Expected: {{ $record->expected_delivery_date ?? '-' }}</span>
        <strong>Delivered: {{ $record->actual_delivery_date ?? '-' }}</strong>
      </div>
    @endforeach
  @endif
</div>
@endsection

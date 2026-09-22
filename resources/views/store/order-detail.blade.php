@extends('layouts.app')

@section('title', 'Order Detail')

@section('content')
<div class="store-page-head"><h1>Order #{{ $header->order_id }}</h1><p>Status: {{ ucwords(str_replace('_', ' ', $header->order_status)) }}</p></div>
<div class="store-panel">
  @foreach ($items as $item)
    <div class="store-order-detail-row">
      <div><strong>{{ $item->product_name }}</strong><span>{{ $item->order_number }}</span></div>
      <div>Qty {{ $item->quantity }} | PKR {{ number_format($item->unit_price, 2) }}</div>
      <div>{{ ucwords(str_replace('_', ' ', $item->item_status)) }}</div>
      @if ($item->has_warranty)<div><i class="bi bi-shield-check"></i> Warranty {{ $item->warranty_months }} months</div>@endif
      @if (! in_array($item->item_status, ['dispatched', 'delivered', 'returned', 'replaced']))<button>Cancel Order</button>@endif
      @if ($item->item_status === 'delivered')<button>Return</button><button>Replace</button>@endif
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

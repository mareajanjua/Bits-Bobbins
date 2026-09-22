@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('content')
<section class="bb-cart-page-shell">
  <div class="bb-cart-full-slider">
    <div class="bb-cart-page-head">
      <button type="button" class="bb-cart-back" onclick="if (history.length > 1) { history.back(); } else { window.location.href='{{ route('store.products') }}'; }">
        <i class="bi bi-arrow-left"></i>
        Back
      </button>
      <h1>Shopping Cart</h1>
    </div>

    @if ($items->isNotEmpty())
      <div class="bb-cart-page-grid">
        <section class="bb-cart-table-card">
          <div class="bb-cart-table-head">
            <span>Product Code</span>
            <span>Quantity</span>
            <span>Total</span>
            <span>Action</span>
          </div>

          @foreach ($items as $item)
            @php
              $frontImage = $item->product->image_front ?? null;
              $stockQty = (int) ($item->product->stock_qty ?? 0);
            @endphp
            <article class="bb-cart-table-row">
              <a href="{{ route('store.product', $item->product->product_id) }}" class="bb-cart-product-cell">
                <span class="bb-cart-product-image">
                  @if ($frontImage)
                    <img src="{{ asset($frontImage) }}" alt="{{ $item->product->product_name }}">
                  @else
                    <i class="bi bi-gift"></i>
                  @endif
                </span>
                <span>
                  <strong>{{ $item->product->product_name }}</strong>
                  <small>Code: {{ $item->product->product_id }}</small>
                </span>
              </a>

              <div class="bb-cart-qty-pill">
                <form method="POST" action="{{ route('cart.update', $item->product->product_id) }}">
                  @csrf
                  <input type="hidden" name="quantity" value="{{ max(0, $item->quantity - 1) }}">
                  <button type="submit" aria-label="Decrease quantity">-</button>
                </form>
                <span>{{ $item->quantity }}</span>
                <form method="POST" action="{{ route('cart.update', $item->product->product_id) }}">
                  @csrf
                  <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                  <button type="submit" aria-label="Increase quantity" @disabled($stockQty > 0 && $item->quantity >= $stockQty)>+</button>
                </form>
              </div>

              <strong class="bb-cart-line-price">PKR {{ number_format($item->line_total, 0) }}</strong>

              <form method="POST" action="{{ route('cart.update', $item->product->product_id) }}" class="bb-cart-remove-form">
                @csrf
                <input type="hidden" name="quantity" value="0">
                <button type="submit" aria-label="Remove {{ $item->product->product_name }}"><i class="bi bi-trash"></i></button>
              </form>
            </article>
          @endforeach

          <a href="{{ route('store.products') }}" class="bb-cart-update-main">Continue Shopping</a>
        </section>

        <aside class="bb-cart-summary-card">
          <h2>Order Summary</h2>
          <div class="bb-cart-voucher">
            <input type="text" placeholder="Discount voucher">
            <button type="button">Apply</button>
          </div>
          <div class="bb-cart-summary-lines">
            <div><span>Sub Total</span><strong>PKR {{ number_format($subtotal, 0) }}</strong></div>
            <div><span>Discount</span><strong>PKR 0</strong></div>
            <div><span>Delivery fee</span><strong>At checkout</strong></div>
          </div>
          <div class="bb-cart-summary-total">
            <span>Total</span>
            <strong>PKR {{ number_format($subtotal, 0) }}</strong>
          </div>
          <p><i class="bi bi-shield-check"></i> Warranty support applies where listed on product pages.</p>
          <a href="{{ route('checkout.index') }}" class="bb-cart-checkout-main">Checkout Now</a>
        </aside>
      </div>
    @else
      <div class="bb-cart-empty-panel">
        <div class="bb-cart-empty-icon"><i class="bi bi-bag"></i></div>
        <h2>Your cart is empty</h2>
        <p>Looks like nothing has made it in yet. Browse the shelves and add something lovely when you are ready.</p>
        <a href="{{ route('store.products') }}" class="bb-cart-empty-button">Shop Products</a>
      </div>
    @endif
  </div>
</section>
@endsection

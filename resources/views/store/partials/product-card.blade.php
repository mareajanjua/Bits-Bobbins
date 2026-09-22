@php
  $variant = $variant ?? 'store';
  $frontImage = $product->image_front ?? null;
  $hoverImage = $product->image_hover ?? null;
  $stockQty = (int) ($product->stock_qty ?? 0);
  $stockClass = $stockQty <= 0 ? 'out' : ($stockQty <= 5 ? 'low' : '');
  $stockLabel = $stockQty <= 0 ? 'Out of stock' : ($stockQty <= 5 ? 'Low stock' : 'In stock');
@endphp

@if ($variant === 'home')
<div class="bb-product-card" data-product-url="{{ route('store.product', $product->product_id) }}" tabindex="0">
  <a href="{{ route('store.product', $product->product_id) }}" class="bb-product-image" aria-label="View {{ $product->product_name }} details">
    @if ($frontImage)
      <img src="{{ asset($frontImage) }}" alt="{{ $product->product_name }}">
      @if ($hoverImage)
        <img src="{{ asset($hoverImage) }}" alt="{{ $product->product_name }} hover" class="bb-product-hover-img">
      @endif
    @else
      <i class="fa fa-gift"></i>
    @endif
  </a>
  <div class="bb-product-body">
    <div class="bb-product-info-row">
      <div>
        <a href="{{ route('store.product', $product->product_id) }}"><h3 class="h6 mb-1">{{ $product->product_name }}</h3></a>
        <div class="bb-product-meta">
          <span class="bb-stock-pill {{ $stockClass }}">({{ $stockLabel }})</span>
          @if (($showRating ?? false) && ($product->review_count ?? 0) > 0)
            <span>{{ number_format($product->avg_rating, 1) }}/5 rating</span>
          @endif
        </div>
      </div>
      <span class="bb-price">PKR {{ number_format($product->price, 0) }}</span>
    </div>
    <form method="POST" action="{{ route('cart.add', $product->product_id) }}" data-cart-form>
      @csrf
      <button type="submit" class="bb-product-buy-btn" @disabled($stockQty <= 0)>
        <i class="fa fa-shopping-cart"></i>
        Add to cart
      </button>
    </form>
  </div>
</div>
@else
<div class="bb-product-card" data-product-url="{{ route('store.product', $product->product_id) }}" tabindex="0">
  <a class="store-product-art" href="{{ route('store.product', $product->product_id) }}">
    @if ($frontImage)
      <img src="{{ asset($frontImage) }}" alt="{{ $product->product_name }}">
      @if ($hoverImage)
        <img src="{{ asset($hoverImage) }}" alt="{{ $product->product_name }} hover" class="store-hover-img">
      @endif
    @else
      <span class="store-product-art-placeholder"><i class="bi bi-gift"></i></span>
    @endif
  </a>
  <div class="store-product-body">
    <div class="store-product-title-row">
      <h3>{{ $product->product_name }}</h3>
      <strong>PKR {{ number_format($product->price, 2) }}</strong>
    </div>
    <div class="store-product-meta">
      <span>Product ID: {{ $product->product_id }}</span>
      @if (! empty($product->subcategory_name))
        <span>Subcategory: {{ $product->subcategory_name }}</span>
      @endif
      <div class="store-stock {{ $stockQty <= 0 ? 'out' : ($stockQty <= 5 ? 'low' : '') }}">
        {{ $stockQty <= 0 ? 'Out of stock' : ($stockQty <= 5 ? 'Low stock' : 'In stock') }}
      </div>
    </div>
    <form method="POST" action="{{ route('cart.add', $product->product_id) }}">
      @csrf
      <button type="submit" class="store-view-product" @disabled($stockQty <= 0)><i class="bi bi-cart3"></i>Add to cart</button>
    </form>
  </div>
</div>
@endif

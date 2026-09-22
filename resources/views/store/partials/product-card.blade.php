<div class="store-product-card" data-product-url="{{ route('store.product', $product->product_id) }}">
  @php
    $frontImage = $product->image_front ?? null;
    $hoverImage = $product->image_hover ?? null;
    $stockQty = (int) ($product->stock_qty ?? 0);
  @endphp
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

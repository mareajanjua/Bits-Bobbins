@extends('layouts.app')

@section('title', $record->product_name)

@section('content')
@php
  $stockQty = (int) ($record->stock_qty ?? 0);
  $stockLabel = $stockQty <= 0 ? 'Out of stock' : ($stockQty <= 5 ? 'Low stock' : 'In stock');
  $detailImages = collect([$record->image_front, $record->image_hover])->filter()->unique()->values();
@endphp

<section class="bb-product-detail-page">
  <div class="bb-product-gallery">
    @if ($detailImages->count() > 1)
      <div class="bb-product-thumbs" aria-label="Product images">
        @foreach ($detailImages as $image)
          <button type="button" class="bb-product-thumb {{ $loop->first ? 'is-active' : '' }}" data-detail-image="{{ $loop->index }}" aria-label="Show {{ $record->product_name }} image {{ $loop->iteration }}">
            <img src="{{ asset($image) }}" alt="">
          </button>
        @endforeach
      </div>
    @endif

    <div class="bb-product-visual">
      @if ($detailImages->isNotEmpty())
        @foreach ($detailImages as $image)
          <img src="{{ asset($image) }}" alt="{{ $record->product_name }} image {{ $loop->iteration }}" class="bb-detail-image {{ $loop->first ? 'is-active' : '' }}">
        @endforeach
      @else
        <i class="bi bi-gift"></i>
      @endif
    </div>
  </div>

  <div class="bb-product-info">
    <div class="bb-product-rating" aria-label="Customer rating">
      @if ($averageRating)
        <span>{{ str_repeat('★', (int) round($averageRating)) }} {{ $averageRating }}/5</span>
        <small>{{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }}</small>
      @else
        <span>No reviews yet</span>
      @endif
    </div>
    <h1>{{ $record->product_name }}</h1>
    <div class="bb-product-subtitle"><i class="bi bi-patch-check-fill"></i>{{ $record->category_name }} Pick</div>

    <div class="bb-product-tabs" role="list">
      <button type="button">Description</button>
      <button type="button">Details</button>
      <button type="button">Stock</button>
      <button type="button">Warranty</button>
    </div>

    <p class="bb-product-description">
      {{ $record->description ?: "A cheerful {$record->category_name} find from Bits&Bobbins, chosen for playful style, easy gifting, and everyday family shopping." }}
    </p>

    <div class="bb-product-purchase">
      <div class="bb-product-option is-selected">
        <span><i class="bi bi-cash-coin"></i> One-Time Purchase</span>
        <strong>PKR {{ number_format($record->price, 0) }}</strong>
      </div>
      <div class="bb-product-option">
        <span><i class="bi bi-upc-scan"></i> Product ID</span>
        <strong>{{ $record->product_id }}</strong>
      </div>
      <div class="bb-product-option">
        <span><i class="bi bi-box-seam"></i> Availability</span>
        <strong>{{ $stockLabel }}</strong>
      </div>
    </div>

    <form class="bb-product-buy-row" method="POST" action="{{ route('cart.add', $record->product_id) }}">
      @csrf
      <div class="bb-product-qty">
        <button type="button" data-qty-step="-1" aria-label="Decrease quantity">-</button>
        <input type="number" name="quantity" value="1" min="1" aria-label="Quantity">
        <button type="button" data-qty-step="1" aria-label="Increase quantity">+</button>
      </div>
      <button class="bb-product-add" type="submit" @disabled($stockQty <= 0)>
        <i class="bi bi-cart3"></i>
        PKR {{ number_format($record->price, 0) }} - Add to Cart
      </button>
    </form>
  </div>
</section>

<section class="bb-product-details-band">
  <div class="bb-product-details-inner">
    <h2>Product Details</h2>
    <div class="bb-product-detail-list">
      @foreach ($details as $detail)
        <details class="bb-product-detail-item" @if($loop->first) open @endif>
          <summary>{{ $detail->title }}</summary>
          <p>{{ $detail->body }}</p>
        </details>
      @endforeach
    </div>
    <p class="bb-product-detail-help">Have a question we have not answered here? Our team is happy to help with product questions.</p>
    <a class="bb-product-contact-btn" href="{{ route('store.about') }}#about-contact">Get in Touch</a>
  </div>
</section>

<section class="bb-product-reviews-section">
  <div class="bb-product-reviews-inner">
    <div class="bb-product-reviews-head">
      <h2>Customer Reviews</h2>
      <span>{{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }}</span>
    </div>

    @forelse ($reviews as $review)
      <article class="bb-product-review-card">
        <div class="bb-product-review-top">
          <strong>{{ $review->full_name }}</strong>
          <span>{{ str_repeat('★', (int) $review->rating) }} <small>{{ $review->rating }}/5</small></span>
        </div>
        <p>{{ $review->message }}</p>
      </article>
    @empty
      <div class="bb-product-review-empty">No customer reviews for this product yet.</div>
    @endforelse
  </div>
</section>

@if ($related->isNotEmpty())
<section class="store-section bb-related-section">
  <div class="store-section-head">
    <h2>More From {{ $record->category_name }}</h2>
    <a class="bb-related-category-btn" href="{{ route('store.products', ['category' => $record->category_code]) }}">View Category</a>
  </div>
  <div class="swiper bb-related-swiper">
    <div class="swiper-wrapper">
      @foreach ($related as $product)
        <div class="swiper-slide">
          @include('store.partials.product-card', ['product' => $product])
        </div>
      @endforeach
    </div>
    <div class="bb-related-swiper-actions">
      <button type="button" class="bb-related-prev" aria-label="Previous related products"><i class="bi bi-arrow-left"></i></button>
      <button type="button" class="bb-related-next" aria-label="Next related products"><i class="bi bi-arrow-right"></i></button>
    </div>
  </div>
</section>
@endif

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  (() => {
    const images = [...document.querySelectorAll('.bb-detail-image')];
    const thumbs = [...document.querySelectorAll('[data-detail-image]')];
    const quantityInput = document.querySelector('.bb-product-qty input');

    const showImage = (index) => {
      images.forEach((image, imageIndex) => image.classList.toggle('is-active', imageIndex === index));
      thumbs.forEach((thumb, thumbIndex) => thumb.classList.toggle('is-active', thumbIndex === index));
    };

    thumbs.forEach((thumb) => {
      thumb.addEventListener('click', () => showImage(Number(thumb.dataset.detailImage)));
    });

    document.querySelectorAll('[data-qty-step]').forEach((button) => {
      button.addEventListener('click', () => {
        if (!quantityInput) return;
        const next = Math.max(1, Number(quantityInput.value || 1) + Number(button.dataset.qtyStep));
        quantityInput.value = next;
      });
    });

    if (window.Swiper && document.querySelector('.bb-related-swiper')) {
      new Swiper('.bb-related-swiper', {
        slidesPerView: 1.15,
        spaceBetween: 18,
        navigation: {
          nextEl: '.bb-related-next',
          prevEl: '.bb-related-prev'
        },
        breakpoints: {
          640: { slidesPerView: 2.2 },
          980: { slidesPerView: 3.4 },
          1240: { slidesPerView: 4.2 }
        }
      });
    }
  })();
</script>
@endsection

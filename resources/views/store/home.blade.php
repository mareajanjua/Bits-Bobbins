@extends('layouts.app')

@section('title', 'Bits&Bobbins Store')

@section('content')
<section class="store-hero">
  <div>
    <span>Retail essentials online</span>
    <h1>Bits&Bobbins</h1>
    <p>Browse gifts, dolls, files, and useful everyday products without an account. Login only when you are ready to place an order.</p>
    <a href="{{ route('store.products') }}" class="store-hero-btn"><i class="bi bi-bag-heart"></i> Shop Products</a>
  </div>
</section>

<section class="store-section">
  <div class="store-section-head"><h2>Shop by Category</h2><a href="{{ route('store.products') }}">View all</a></div>
  <div class="store-category-grid">
    @forelse ($categories as $category)
      <a href="{{ route('store.products', ['category' => $category->category_code]) }}"><i class="bi bi-box"></i><strong>{{ $category->category_name }}</strong><span>{{ $category->category_code }}</span></a>
    @empty
      <p>No categories available yet.</p>
    @endforelse
  </div>
</section>

<section class="store-section">
  <div class="store-section-head"><h2>Featured / New Arrivals</h2><a href="{{ route('store.products', ['sort' => 'newest']) }}">Newest</a></div>
  <div class="store-product-grid">
    @forelse ($newArrivals as $product)
      @include('store.partials.product-card', ['product' => $product])
    @empty
      <p>No products available yet.</p>
    @endforelse
  </div>
</section>

<section class="store-section">
  <div class="store-section-head"><h2>Best Sellers</h2><a href="{{ route('store.products') }}">Explore</a></div>
  <div class="store-product-grid">
    @forelse ($bestSellers as $product)
      @include('store.partials.product-card', ['product' => $product])
    @empty
      <p>No best sellers yet.</p>
    @endforelse
  </div>
</section>

<section class="store-two-col">
  <div class="store-panel">
    <h2><i class="bi bi-chat-square-heart"></i> Customer Feedback</h2>
    @forelse ($feedback as $item)
      <blockquote><strong>{{ $item->full_name }}</strong><span>{{ $item->rating ?? 'N/A' }}/5</span><p>{{ $item->message }}</p></blockquote>
    @empty
      <p>No feedback available yet.</p>
    @endforelse
  </div>
  <div class="store-panel">
    <h2><i class="bi bi-question-circle"></i> FAQ</h2>
    @forelse ($faqs as $faq)
      <details><summary>{{ $faq->question }}</summary><p>{{ $faq->answer }}</p></details>
    @empty
      <p>No FAQs available yet.</p>
    @endforelse
    <a class="store-inline-link" href="{{ route('store.faq') }}">Open full FAQ</a>
  </div>
</section>
@endsection

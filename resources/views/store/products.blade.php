@extends('layouts.app')

@php
  $selectedCategory = $categories->firstWhere('category_code', request('category'));
  $selectedSubcategory = $subcategories->firstWhere('subcategory_id', (int) request('subcategory'));
  $catalogTitle = $selectedSubcategory->subcategory_name ?? $selectedCategory->category_name ?? 'All Products';
  $activeFilters = collect([
    request('q') ? 'Search: ' . request('q') : null,
    request('min_price') || request('max_price') ? 'Price: ' . (request('min_price') ?: '0') . '-' . (request('max_price') ?: 'Any') : null,
    request('in_stock') ? 'In stock' : null,
    request('warranty') ? 'Warranty: ' . ucfirst(request('warranty')) : null,
  ])->filter();
@endphp

@section('title', $catalogTitle)

@section('content')
<section class="bb-catalog-page">
  <aside class="bb-catalog-filter">
    <form method="GET" action="{{ route('store.products') }}">
      <div class="bb-filter-title">Filter</div>

      <details class="bb-filter-group" open>
        <summary>Search</summary>
        <input name="q" value="{{ request('q') }}" placeholder="Product name">
      </details>

      <details class="bb-filter-group" open>
        <summary>Category</summary>
        <select name="category" id="catalog-category-select">
          <option value="">All Categories</option>
          @foreach ($categories as $category)
            <option value="{{ $category->category_code }}" @selected(request('category') === $category->category_code)>{{ $category->category_name }}</option>
          @endforeach
        </select>
      </details>

      <details class="bb-filter-group" open>
        <summary>Subcategory</summary>
        <select name="subcategory" id="catalog-subcategory-select">
          <option value="">All Subcategories</option>
          @foreach ($subcategories as $subcategory)
            <option value="{{ $subcategory->subcategory_id }}" data-category="{{ $subcategory->category_code }}" @selected((string) request('subcategory') === (string) $subcategory->subcategory_id)>{{ $subcategory->subcategory_name }}</option>
          @endforeach
        </select>
      </details>

      <details class="bb-filter-group" open>
        <summary>Price</summary>
        <div class="bb-price-fields">
          <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="From">
          <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="To">
        </div>
      </details>

      <details class="bb-filter-group" open>
        <summary>Stock</summary>
        <label class="bb-filter-check"><input type="checkbox" name="in_stock" value="1" @checked(request('in_stock'))> In stock only</label>
      </details>

      <details class="bb-filter-group">
        <summary>Warranty</summary>
        <label class="bb-filter-check"><input type="radio" name="warranty" value="" @checked(! request('warranty'))> Any</label>
        <label class="bb-filter-check"><input type="radio" name="warranty" value="yes" @checked(request('warranty') === 'yes')> Yes</label>
        <label class="bb-filter-check"><input type="radio" name="warranty" value="no" @checked(request('warranty') === 'no')> No</label>
      </details>

      <button type="submit">Apply</button>
    </form>
  </aside>

  <section class="bb-catalog-content">
    <div class="bb-catalog-crumb">Homepage / {{ $catalogTitle }}</div>
    <div class="bb-catalog-head">
      <div>
        <h1>{{ $catalogTitle }}</h1>
        <p>Showed {{ $products->total() }} goods</p>
      </div>
      <form class="bb-catalog-sort" method="GET" action="{{ route('store.products') }}">
        @foreach (request()->except('sort', 'page') as $key => $value)
          @if (is_array($value))
            @foreach ($value as $item)
              <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
          @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <label>Sort by</label>
        <select name="sort" onchange="this.form.submit()">
          <option value="">Relevancy</option>
          <option value="price_low" @selected(request('sort') === 'price_low')>Price Low-High</option>
          <option value="price_high" @selected(request('sort') === 'price_high')>Price High-Low</option>
          <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
        </select>
      </form>
    </div>

    @if ($activeFilters->isNotEmpty())
      <div class="bb-active-filters">
        @foreach ($activeFilters as $filter)
          <span>{{ $filter }}</span>
        @endforeach
        <a href="{{ route('store.products') }}">Clear all filters</a>
      </div>
    @endif

    <div class="bb-catalog-grid">
      @forelse ($products as $product)
        @include('store.partials.product-card', ['product' => $product])
      @empty
        <div class="store-empty">No products found.</div>
      @endforelse
    </div>

    @if ($products->hasMorePages())
      <div class="bb-catalog-more">
        <a href="{{ $products->nextPageUrl() }}">View More Products</a>
      </div>
    @endif
  </section>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('catalog-category-select');
    const subcategorySelect = document.getElementById('catalog-subcategory-select');

    if (!categorySelect || !subcategorySelect) {
      return;
    }

    const subcategoryOptions = Array.from(subcategorySelect.options);

    function syncSubcategories(resetSelection) {
      const category = categorySelect.value;
      let selectedOptionStillVisible = false;

      subcategoryOptions.forEach(function (option) {
        const belongsToCategory = !option.dataset.category || !category || option.dataset.category === category;
        option.hidden = !belongsToCategory;
        option.disabled = !belongsToCategory;

        if (belongsToCategory && option.selected) {
          selectedOptionStillVisible = true;
        }
      });

      if (resetSelection || !selectedOptionStillVisible) {
        subcategorySelect.value = '';
      }
    }

    syncSubcategories(false);
    categorySelect.addEventListener('change', function () {
      syncSubcategories(true);
    });
  });
</script>
@endsection

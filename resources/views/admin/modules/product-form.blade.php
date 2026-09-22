@extends('layouts.admin')

@section('title', $record ? 'Edit Product' : 'Add Product')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-box-seam"></i> {{ $record ? 'Edit Product' : 'Add Product' }}</h1>
    <p class="page-subtitle">Category drives the required 2-digit code. Product number completes the 7-digit ID.</p>
  </div>
</div>

<div class="card dashboard-overview-card">
  <form method="POST" action="{{ $record ? route('admin.products.update', $record->product_id) : route('admin.products.store') }}" class="row g-3" enctype="multipart/form-data">
    @csrf
    @if ($errors->any())
      <div class="col-12">
        <div class="alert alert-danger mb-0">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      </div>
    @endif
    <div class="col-md-6">
      <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <label class="form-label-custom mb-0">Category</label>
        <a href="{{ route('admin.categories.create') }}" class="btn-custom btn-custom-light btn-custom-sm"><i class="bi bi-plus-lg"></i>Add Category</a>
      </div>
      @if ($categories->isEmpty())
        <div class="alert alert-warning mb-0">
          No categories available yet. Add a category first, then come back to add the product.
        </div>
      @else
        <select name="category_code" class="form-select-custom" required>
          <option value="">Select Category</option>
          @foreach ($categories as $category)
            <option value="{{ $category->category_code }}" @selected(old('category_code', $record->category_code ?? '') === $category->category_code)>
              {{ $category->category_code }} - {{ $category->category_name }}
            </option>
          @endforeach
        </select>
      @endif
    </div>
    <div class="col-md-6">
      <label class="form-label-custom">Product Name</label>
      <input type="text" name="product_name" class="form-control-custom" value="{{ old('product_name', $record->product_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
      <label class="form-label-custom">Subcategory</label>
      <select name="subcategory_id" class="form-select-custom" id="product-subcategory-select">
        <option value="">No Subcategory</option>
        @foreach ($subcategories as $subcategory)
          <option value="{{ $subcategory->subcategory_id }}" data-category="{{ $subcategory->category_code }}" @selected((string) old('subcategory_id', $record->subcategory_id ?? '') === (string) $subcategory->subcategory_id)>
            {{ $subcategory->subcategory_name }}
          </option>
        @endforeach
      </select>
      <small class="text-muted-green">Optional. It will show choices for the selected category only.</small>
    </div>
    @if ($record)
      <div class="col-md-6">
        <label class="form-label-custom">7-Digit Product ID</label>
        <input type="text" class="form-control-custom" value="{{ $record->product_id }}" readonly>
      </div>
    @endif
    <div class="col-12">
      <label class="form-label-custom">Description</label>
      <textarea name="description" class="form-control-custom" rows="4">{{ old('description', $record->description ?? '') }}</textarea>
    </div>
    <div class="col-12">
      <div class="admin-product-detail-editor">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
          <div>
            <label class="form-label-custom mb-1">Product Details</label>
            <small class="text-muted-green d-block">These show in the brown Product Details section on the customer product page.</small>
          </div>
        </div>
        @for ($index = 0; $index < 5; $index++)
          @php
            $detail = $productDetails[$index] ?? (object) ['title' => '', 'body' => ''];
          @endphp
          <div class="admin-product-detail-row">
            <div>
              <label class="form-label-custom">Question {{ $index + 1 }}</label>
              <input type="text" name="detail_titles[]" class="form-control-custom" value="{{ old("detail_titles.$index", $detail->title) }}" placeholder="Example: What makes this product special?">
            </div>
            <div>
              <label class="form-label-custom">Answer {{ $index + 1 }}</label>
              <textarea name="detail_bodies[]" class="form-control-custom" rows="3" placeholder="Write the answer shown on product page.">{{ old("detail_bodies.$index", $detail->body) }}</textarea>
            </div>
          </div>
        @endfor
      </div>
    </div>
    <div class="col-md-6">
      <label class="form-label-custom">Front Product Image {{ $record ? '' : '*' }}</label>
      @if (! empty($record->image_front))
        <div class="admin-product-preview"><img src="{{ asset($record->image_front) }}" alt="{{ $record->product_name }}"></div>
      @endif
      <input type="file" name="image_front" class="form-control-custom" accept="image/*" @required(! $record)>
      <small class="text-muted-green">Shown normally on product cards.</small>
    </div>
    <div class="col-md-6">
      <label class="form-label-custom">Mouse Hover Product Image</label>
      @if (! empty($record->image_hover))
        <div class="admin-product-preview"><img src="{{ asset($record->image_hover) }}" alt="{{ $record->product_name }} hover"></div>
      @endif
      <input type="file" name="image_hover" class="form-control-custom" accept="image/*">
      <small class="text-muted-green">Shown when customer moves mouse over the product image.</small>
    </div>
    <div class="col-md-4">
      <label class="form-label-custom">Price</label>
      <div class="currency-input-wrap">
        <span>PKR</span>
        <input type="number" name="price" step="0.01" min="0" class="form-control-custom currency-input" value="{{ old('price', $record->price ?? '') }}" required>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label-custom">{{ $record ? 'Stock Quantity' : 'Initial Stock Quantity' }}</label>
      <input type="number" name="stock_quantity" min="0" class="form-control-custom" value="{{ old('stock_quantity', $stock->quantity_available ?? 0) }}">
    </div>
    <div class="col-md-4">
      <label class="form-label-custom">Warranty Duration (Months)</label>
      <input type="number" name="warranty_months" min="0" class="form-control-custom" value="{{ old('warranty_months', $record->warranty_months ?? '') }}">
    </div>
    <div class="col-12">
      <label class="form-check-custom">
        <input type="checkbox" name="has_warranty" value="1" class="form-check-input-custom" @checked(old('has_warranty', $record->has_warranty ?? false))>
        Warranty Available
      </label>
    </div>
    <div class="col-12">
      <label class="form-check-custom">
        <input type="checkbox" name="is_active" value="1" class="form-check-input-custom" @checked(old('is_active', $record->is_active ?? true))>
        Active Product
      </label>
    </div>
    <div class="col-12">
      <button type="submit" class="btn-custom btn-custom-primary">Save Product</button>
      <a href="{{ route('admin.products.index') }}" class="btn-custom btn-custom-light">Cancel</a>
    </div>
  </form>
</div>
<script>
  (() => {
    const category = document.querySelector('select[name="category_code"]');
    const subcategory = document.getElementById('product-subcategory-select');
    if (!category || !subcategory) return;

    const filterSubcategories = () => {
      const selectedCategory = category.value;
      Array.from(subcategory.options).forEach((option) => {
        if (!option.value) {
          option.hidden = false;
          return;
        }

        const visible = option.dataset.category === selectedCategory;
        option.hidden = !visible;
        if (!visible && option.selected) {
          subcategory.value = '';
        }
      });
    };

    category.addEventListener('change', filterSubcategories);
    filterSubcategories();
  })();
</script>
@endsection

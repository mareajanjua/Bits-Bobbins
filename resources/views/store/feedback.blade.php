@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<section class="bb-feedback-page">
  <div class="bb-feedback-form-card">
    <div class="bb-feedback-form-head">
      <p>After Purchase</p>
      <h1>Leave Feedback</h1>
      <span>Choose a product you bought, add a star rating, and share your review.</span>
    </div>

    @if (session('status'))
      <div class="store-alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="store-error">{{ $errors->first() }}</div>
    @endif

    @if ($purchasedItems->isNotEmpty())
      <form method="POST" action="{{ route('customer.feedback.store') }}" class="bb-feedback-form">
        @csrf
        <label>
          <span>Purchased product</span>
          <select name="product_id" required>
            <option value="">Select product</option>
            @foreach ($purchasedItems as $item)
              <option value="{{ $item->product_id }}" data-order-id="{{ $item->order_id }}" @selected(old('product_id') === $item->product_id)>
                {{ $item->product_name }} - {{ $item->order_number }}
              </option>
            @endforeach
          </select>
        </label>
        <input type="hidden" name="order_id" value="{{ old('order_id') }}" data-feedback-order>

        <label>
          <span>Star rating</span>
          <select name="rating" required>
            <option value="">Select rating</option>
            @for ($rating = 5; $rating >= 1; $rating--)
              <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ str_repeat('★', $rating) }} {{ $rating }}/5</option>
            @endfor
          </select>
        </label>

        <label>
          <span>Review</span>
          <textarea name="message" rows="5" required>{{ old('message') }}</textarea>
        </label>

        <button type="submit"><i class="bi bi-chat-square-heart"></i> Submit Feedback</button>
      </form>
    @else
      <div class="bb-feedback-form-empty">
        <i class="bi bi-bag-check"></i>
        <strong>No purchased products yet</strong>
        <span>After you place an order, your purchased products will appear here for review.</span>
      </div>
    @endif
  </div>
</section>

<script>
  (() => {
    const productSelect = document.querySelector('select[name="product_id"]');
    const orderInput = document.querySelector('[data-feedback-order]');
    const syncOrder = () => {
      if (!productSelect || !orderInput) return;
      orderInput.value = productSelect.selectedOptions[0]?.dataset.orderId || '';
    };

    productSelect?.addEventListener('change', syncOrder);
    syncOrder();
  })();
</script>
@endsection

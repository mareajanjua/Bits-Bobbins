@extends('layouts.app')

@section('title', 'Checkout')

@php
  $shipping = 0;
  $total = $subtotal + $shipping;
@endphp

@section('content')
<section class="bb-checkout-page">
  <form method="POST" action="{{ route('checkout.place') }}" class="bb-checkout-layout">
    @csrf

    <div class="bb-checkout-main">
      <header class="bb-checkout-head">
        <p>Secure Checkout</p>
        <h1>Checkout</h1>
      </header>

      @if ($errors->any())
        <div class="store-error">{{ $errors->first() }}</div>
      @endif

      @if (! session('customer_id'))
        <section class="bb-checkout-section">
          <div class="bb-checkout-section-title">
            <i class="bi bi-person"></i>
            <h2>Information</h2>
          </div>
          <div class="bb-checkout-fields">
            <label><span>Full name</span><input name="guest_name" value="{{ old('guest_name') }}" required></label>
            <label><span>Email</span><input type="email" name="guest_email" value="{{ old('guest_email') }}" required></label>
            <label><span>Phone number</span><input name="guest_phone" value="{{ old('guest_phone') }}"></label>
          </div>
        </section>
      @endif

      <section class="bb-checkout-section">
        <div class="bb-checkout-section-title">
          <i class="bi bi-geo-alt"></i>
          <h2>Delivery Address</h2>
        </div>

        @if ($addresses->isNotEmpty())
          <label class="bb-checkout-full">
            <span>Saved address</span>
            <select name="address_id">
              <option value="">Use a new address</option>
              @foreach ($addresses as $address)
                <option value="{{ $address->address_id }}" @selected(old('address_id') == $address->address_id)>
                  {{ $address->address_line1 }}, {{ $address->city }}
                </option>
              @endforeach
            </select>
          </label>
        @endif

        <div class="bb-checkout-fields">
          <label><span>Address line 1</span><input name="address_line1" value="{{ old('address_line1') }}"></label>
          <label><span>Address line 2</span><input name="address_line2" value="{{ old('address_line2') }}"></label>
          <label><span>City</span><input name="city" value="{{ old('city') }}"></label>
          <label><span>State</span><input name="state" value="{{ old('state') }}"></label>
          <label><span>Postal code</span><input name="postal_code" value="{{ old('postal_code') }}"></label>
        </div>
      </section>

      <section class="bb-checkout-section">
        <div class="bb-checkout-section-title">
          <i class="bi bi-truck"></i>
          <h2>Delivery Type</h2>
        </div>
        <label class="bb-checkout-full">
          <span>1-digit delivery type</span>
          <select name="delivery_code" required>
            @foreach ($deliveryTypes as $type)
              <option value="{{ $type->delivery_code }}" @selected(old('delivery_code') === $type->delivery_code)>
                {{ $type->delivery_code }} - {{ $type->delivery_name }}
              </option>
            @endforeach
          </select>
        </label>
      </section>

      <section class="bb-checkout-section">
        <div class="bb-checkout-section-title">
          <i class="bi bi-credit-card"></i>
          <h2>Payment</h2>
        </div>

        <div class="bb-payment-options">
          <label><input type="radio" name="payment_method" value="credit_card" @checked(old('payment_method', 'credit_card') === 'credit_card')><span><i class="bi bi-credit-card-2-front"></i>Credit Card</span></label>
          <label><input type="radio" name="payment_method" value="cheque" @checked(old('payment_method') === 'cheque')><span><i class="bi bi-bank"></i>Cheque</span></label>
          <label><input type="radio" name="payment_method" value="vpp_cod" @checked(old('payment_method') === 'vpp_cod')><span><i class="bi bi-box-seam"></i>VPP</span></label>
          <label><input type="radio" name="payment_method" value="dd" @checked(old('payment_method') === 'dd')><span><i class="bi bi-receipt"></i>DD</span></label>
        </div>

        <div class="bb-payment-fields" data-payment-fields="credit_card">
          <div class="bb-checkout-fields">
            <label><span>Card number</span><input name="card_number" value="{{ old('card_number') }}" inputmode="numeric"></label>
            <label><span>Cardholder name</span><input name="card_holder_name" value="{{ old('card_holder_name') }}"></label>
            <label><span>Expiration date</span><input name="card_expiry" value="{{ old('card_expiry') }}" placeholder="MM/YY" inputmode="numeric" maxlength="5" data-card-expiry></label>
            <label><span>CVV</span><input name="card_cvv" value="{{ old('card_cvv') }}" inputmode="numeric"></label>
          </div>
        </div>

        <div class="bb-payment-fields" data-payment-fields="cheque">
          <div class="bb-checkout-fields">
            <label><span>Cheque number</span><input name="cheque_number" value="{{ old('cheque_number') }}"></label>
            <label><span>Bank name</span><input name="bank_name" value="{{ old('bank_name') }}"></label>
            <label><span>Cheque date</span><input type="date" name="cheque_date" value="{{ old('cheque_date') }}"></label>
          </div>
        </div>

        <div class="bb-payment-fields" data-payment-fields="vpp_cod">
          <div class="bb-checkout-note"><i class="bi bi-info-circle"></i> VPP payment is collected when the parcel is delivered.</div>
        </div>

        <div class="bb-payment-fields" data-payment-fields="dd">
          <div class="bb-checkout-fields">
            <label><span>DD number</span><input name="dd_number" value="{{ old('dd_number') }}"></label>
            <label><span>Bank name</span><input name="bank_name" value="{{ old('bank_name') }}"></label>
            <label><span>DD date</span><input type="date" name="dd_date" value="{{ old('dd_date') }}"></label>
          </div>
        </div>
      </section>

      <button class="bb-checkout-place" type="submit"><i class="bi bi-lock"></i> Place Order</button>
    </div>

    <aside class="bb-checkout-bag">
      <h2>Shopping Bag ({{ $items->sum('quantity') }})</h2>
      <div class="bb-checkout-products">
        @foreach ($items as $item)
          @php $frontImage = $item->product->image_front ?? null; @endphp
          <article class="bb-checkout-product">
            <a class="bb-checkout-product-img" href="{{ route('store.product', $item->product->product_id) }}">
              @if ($frontImage)
                <img src="{{ asset($frontImage) }}" alt="{{ $item->product->product_name }}">
              @else
                <i class="bi bi-gift"></i>
              @endif
            </a>
            <div>
              <h3>{{ $item->product->product_name }}</h3>
              <p>Code: {{ $item->product->product_id }}</p>
              <p>Quantity: {{ $item->quantity }}</p>
            </div>
            <strong>PKR {{ number_format($item->line_total, 0) }}</strong>
          </article>
        @endforeach
      </div>

      <div class="bb-checkout-totals">
        <div><span>Product subtotal</span><strong>PKR {{ number_format($subtotal, 0) }}</strong></div>
        <div><span>Delivery charges</span><strong>{{ $shipping > 0 ? 'PKR ' . number_format($shipping, 0) : 'Free' }}</strong></div>
        <div class="bb-checkout-total"><span>Total amount</span><strong>PKR {{ number_format($total, 0) }}</strong></div>
      </div>
    </aside>
  </form>
</section>

<script>
  (() => {
    const options = document.querySelectorAll('input[name="payment_method"]');
    const groups = document.querySelectorAll('[data-payment-fields]');

    const syncPaymentFields = () => {
      const selected = document.querySelector('input[name="payment_method"]:checked')?.value || 'credit_card';
      groups.forEach((group) => {
        const isActive = group.dataset.paymentFields === selected;
        group.hidden = !isActive;
        group.querySelectorAll('input, select, textarea').forEach((field) => {
          field.disabled = !isActive;
        });
      });
    };

    options.forEach((option) => option.addEventListener('change', syncPaymentFields));
    syncPaymentFields();

    const expiry = document.querySelector('[data-card-expiry]');
    expiry?.addEventListener('input', () => {
      const digits = expiry.value.replace(/\D/g, '').slice(0, 4);
      expiry.value = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
    });
  })();
</script>
@endsection

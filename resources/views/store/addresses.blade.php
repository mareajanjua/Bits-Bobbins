@extends('layouts.app')

@section('title', 'My Addresses')

@section('content')
<section class="bb-address-page">
  <header class="bb-address-head">
    <div>
      <p>Checkout Details</p>
      <h1>My Addresses</h1>
    </div>
    <span>Manage shipping addresses used during checkout.</span>
  </header>

  @if (session('status'))
    <div class="store-alert">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="store-error">{{ $errors->first() }}</div>
  @endif

  <div class="bb-address-grid">
    <section class="bb-address-card bb-address-saved">
      <div class="bb-address-card-head">
        <span><i class="bi bi-geo-alt"></i></span>
        <div>
          <h2>Saved Addresses</h2>
          <p>{{ $addresses->count() }} saved {{ \Illuminate\Support\Str::plural('address', $addresses->count()) }}</p>
        </div>
      </div>

      <div class="bb-address-list">
        @forelse ($addresses as $address)
          <article class="bb-address-item">
            <div class="bb-address-item-icon"><i class="bi bi-house-door"></i></div>
            <div>
              <strong>{{ $address->address_line1 }}</strong>
              @if ($address->address_line2)
                <span>{{ $address->address_line2 }}</span>
              @endif
              <span>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</span>
            </div>
          </article>
        @empty
          <div class="bb-address-empty">
            <i class="bi bi-map"></i>
            <strong>No saved addresses yet</strong>
            <p>Add your delivery details once and they will be ready at checkout.</p>
          </div>
        @endforelse
      </div>
    </section>

    <section class="bb-address-card bb-address-form-card">
      <div class="bb-address-card-head">
        <span><i class="bi bi-plus-lg"></i></span>
        <div>
          <h2>Add Address</h2>
          <p>Save a new delivery location.</p>
        </div>
      </div>

      <form method="POST" action="{{ route('customer.addresses.store') }}" class="bb-address-form">
        @csrf
        <label>
          <span>Address line 1</span>
          <input name="address_line1" value="{{ old('address_line1') }}" required>
        </label>
        <label>
          <span>Address line 2</span>
          <input name="address_line2" value="{{ old('address_line2') }}">
        </label>
        <div class="bb-address-form-row">
          <label>
            <span>City</span>
            <input name="city" value="{{ old('city') }}" required>
          </label>
          <label>
            <span>State</span>
            <input name="state" value="{{ old('state') }}" required>
          </label>
        </div>
        <label>
          <span>Postal code</span>
          <input name="postal_code" value="{{ old('postal_code') }}" required>
        </label>
        <button type="submit"><i class="bi bi-check2"></i> Save Address</button>
      </form>
    </section>
  </div>
</section>
@endsection

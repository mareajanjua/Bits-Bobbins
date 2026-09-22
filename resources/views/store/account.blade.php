@extends('layouts.app')

@section('title', 'Your Account')

@section('content')
<section class="bb-account-page">
  <header class="bb-account-hero">
    <div class="bb-account-profile-photo">
      <img src="{{ ($customer->profile_photo ?? null) ? asset($customer->profile_photo) : asset('assets/dashboard/images/avatar.png') }}" alt="{{ $customer->full_name }} profile photo">
    </div>
    <div>
      <p>Your Account</p>
      <h1>Welcome back, {{ explode(' ', trim($customer->full_name))[0] ?: 'there' }}</h1>
      <span>Manage your profile, orders, addresses and password in one place.</span>
    </div>
    <form method="POST" action="{{ route('customer.logout') }}">
      @csrf
      <button class="bb-account-logout" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
    </form>
  </header>

  @if (session('status'))
    <div class="store-alert">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="store-error">{{ $errors->first() }}</div>
  @endif

  <div class="bb-account-summary">
    <article><i class="bi bi-bag-check"></i><span>{{ $orderCount }}</span><strong>Orders</strong></article>
    <article><i class="bi bi-geo-alt"></i><span>{{ $addressCount }}</span><strong>Saved Addresses</strong></article>
  </div>

  <div class="bb-account-dashboard">
    <section class="bb-account-panel bb-account-profile-panel">
      <div class="bb-account-panel-head">
        <h2><i class="bi bi-person-badge"></i> Profile</h2>
        <p>Update your customer image and check saved contact details.</p>
      </div>

      <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data" class="bb-account-photo-form">
        @csrf
        <label>
          <span>Profile image</span>
          <input type="file" name="profile_photo" accept="image/*" required>
        </label>
        <button class="bb-account-action" type="submit"><i class="bi bi-upload"></i> Update Image</button>
      </form>

      <dl class="bb-account-details">
        <div><dt>Name</dt><dd>{{ $customer->full_name }}</dd></div>
        <div><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
        <div><dt>Phone</dt><dd>{{ $customer->phone ?: 'Not added' }}</dd></div>
      </dl>
    </section>

    <section class="bb-account-panel">
      <div class="bb-account-panel-head">
        <h2><i class="bi bi-lock"></i> Change Password</h2>
        <p>Use at least 8 characters with letters and numbers.</p>
      </div>
      <form method="POST" action="{{ route('customer.password.update') }}" class="bb-auth-form">
        @csrf
        <label>
          <span>Current password</span>
          <input type="password" name="current_password" required>
        </label>
        <label>
          <span>New password</span>
          <input type="password" name="password" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" required>
        </label>
        <label>
          <span>Confirm new password</span>
          <input type="password" name="password_confirmation" required>
        </label>
        <button class="bb-auth-submit" type="submit"><i class="bi bi-key"></i> Update Password</button>
      </form>
    </section>

    <section class="bb-account-panel">
      <div class="bb-account-panel-head">
        <h2><i class="bi bi-receipt"></i> Recent Orders</h2>
        <a href="{{ route('customer.orders') }}">View all</a>
      </div>
      <div class="bb-account-list">
        @forelse ($orders as $order)
          <a class="bb-account-order" href="{{ route('customer.orders.show', $order->order_id) }}">
            <span>#{{ $order->order_id }}</span>
            <strong>{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</strong>
            <small>{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</small>
          </a>
        @empty
          <p class="bb-account-muted">No orders yet.</p>
        @endforelse
      </div>
    </section>

    <section class="bb-account-panel">
      <div class="bb-account-panel-head">
        <h2><i class="bi bi-truck"></i> Account Shortcuts</h2>
        <p>Fast access to your shopping tools.</p>
      </div>
      <div class="bb-account-shortcuts">
        <a href="{{ route('customer.orders') }}"><i class="bi bi-bag-check"></i><span>My Orders</span></a>
        <a href="{{ route('customer.feedback') }}"><i class="bi bi-chat-square-heart"></i><span>Feedback</span></a>
        <a href="{{ route('store.products') }}"><i class="bi bi-bag"></i><span>Browse Products</span></a>
      </div>
    </section>

    <section class="bb-account-panel bb-account-address-panel">
      <div class="bb-account-panel-head">
        <h2><i class="bi bi-geo-alt"></i> Saved Addresses</h2>
        <p>Add or review checkout delivery addresses here.</p>
      </div>

      <div class="bb-account-address-layout">
        <div class="bb-account-address-list">
          @forelse ($addresses as $address)
            <article class="bb-account-address-item">
              <i class="bi bi-house-door"></i>
              <div>
                <strong>{{ $address->address_line1 }}</strong>
                @if ($address->address_line2)
                  <span>{{ $address->address_line2 }}</span>
                @endif
                <span>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</span>
              </div>
            </article>
          @empty
            <div class="bb-account-address-empty">
              <i class="bi bi-map"></i>
              <strong>No saved addresses yet</strong>
              <span>Add one now and checkout will be quicker.</span>
            </div>
          @endforelse
        </div>

        <form method="POST" action="{{ route('customer.addresses.store') }}" class="bb-account-address-form">
          @csrf
          <input name="address_line1" value="{{ old('address_line1') }}" placeholder="Address line 1" required>
          <input name="address_line2" value="{{ old('address_line2') }}" placeholder="Address line 2">
          <div>
            <input name="city" value="{{ old('city') }}" placeholder="City" required>
            <input name="state" value="{{ old('state') }}" placeholder="State" required>
          </div>
          <input name="postal_code" value="{{ old('postal_code') }}" placeholder="Postal code" required>
          <button type="submit"><i class="bi bi-check2"></i> Save Address</button>
        </form>
      </div>
    </section>
  </div>
</section>
@endsection

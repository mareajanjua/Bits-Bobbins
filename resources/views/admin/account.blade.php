@extends('layouts.admin')

@section('title', 'My Account')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-person"></i> My Account</h1>
    <p class="page-subtitle">Dealer profile, shop details, password, and notification preferences.</p>
  </div>
</div>

@if (session('status'))
  <div class="alert-custom alert-custom-success">
    <i class="bi bi-check-circle alert-custom-icon"></i>
    <div class="alert-custom-content">{{ session('status') }}</div>
  </div>
@endif

@if ($errors->any())
  <div class="alert-custom alert-custom-danger">
    <i class="bi bi-exclamation-triangle alert-custom-icon"></i>
    <div class="alert-custom-content">{{ $errors->first() }}</div>
  </div>
@endif

<div class="row g-3">
  <div class="col-xl-7">
    <div class="card dashboard-overview-card h-100">
      <div class="dashboard-card-header">
        <h2 class="card-title"><i class="bi bi-person-badge"></i> Profile & Shop Information</h2>
      </div>
      <form method="POST" action="{{ route('admin.account.update') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-12 d-flex align-items-center gap-3">
          <img src="{{ $admin->profile_photo ? asset($admin->profile_photo) : asset('assets/dashboard/images/avatar.png') }}"
               alt="Admin profile photo" class="admin-account-photo">
          <div>
            <label class="form-label-custom">Profile Photo</label>
            <input type="file" name="profile_photo" class="form-control-custom" accept="image/*">
            <div class="dashboard-muted mt-1">Photo stays saved until you upload a new one.</div>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label-custom">Admin Name</label>
          <input name="username" class="form-control-custom" value="{{ old('username', $admin->username) }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label-custom">Email</label>
          <input type="email" name="email" class="form-control-custom" value="{{ old('email', $admin->email) }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label-custom">Shop / Business Name</label>
          <input name="shop_name" class="form-control-custom" value="{{ old('shop_name', $admin->shop_name) }}">
        </div>
        <div class="col-12">
          <label class="form-label-custom">Shop Address</label>
          <textarea name="shop_address" class="form-control-custom" rows="3">{{ old('shop_address', $admin->shop_address) }}</textarea>
        </div>
        <div class="col-12">
          <label class="form-label-custom d-block">Notification Preferences</label>
          <label class="form-check-custom"><input type="checkbox" name="notify_returns" class="form-check-input-custom" @checked($admin->notify_returns)> Email me when a return request comes in</label>
          <label class="form-check-custom"><input type="checkbox" name="notify_failed_payments" class="form-check-input-custom" @checked($admin->notify_failed_payments)> Email me when a payment fails</label>
          <label class="form-check-custom"><input type="checkbox" name="notify_low_stock" class="form-check-input-custom" @checked($admin->notify_low_stock)> Email me when stock becomes low</label>
        </div>
        <div class="col-12">
          <button class="btn-custom btn-custom-primary" type="submit"><i class="bi bi-save"></i>Save Account</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="card dashboard-overview-card h-100">
      <div class="dashboard-card-header">
        <h2 class="card-title"><i class="bi bi-lock"></i> Change Password</h2>
      </div>
      <form method="POST" action="{{ route('admin.account.password') }}" class="row g-3">
        @csrf
        <div class="col-12">
          <label class="form-label-custom">Current Password</label>
          <input type="password" name="current_password" class="form-control-custom" required>
        </div>
        <div class="col-12">
          <label class="form-label-custom">New Password</label>
          <input type="password" name="password" class="form-control-custom" required>
        </div>
        <div class="col-12">
          <label class="form-label-custom">Confirm New Password</label>
          <input type="password" name="password_confirmation" class="form-control-custom" required>
        </div>
        <div class="col-12">
          <button class="btn-custom btn-custom-primary" type="submit"><i class="bi bi-key"></i>Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

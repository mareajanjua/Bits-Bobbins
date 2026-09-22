@extends('layouts.admin')

@section('title', $page === 'password' ? 'Change Password' : 'Shop Profile')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-gear"></i> {{ $page === 'password' ? 'Change Password' : 'Shop Profile' }}</h1>
    <p class="page-subtitle">Settings UI placeholder for the admin module.</p>
  </div>
</div>

<div class="card dashboard-overview-card">
  @if ($page === 'password')
    <form class="row g-3">
      <div class="col-md-4"><label class="form-label-custom">Current Password</label><input type="password" class="form-control-custom"></div>
      <div class="col-md-4"><label class="form-label-custom">New Password</label><input type="password" class="form-control-custom"></div>
      <div class="col-md-4"><label class="form-label-custom">Confirm Password</label><input type="password" class="form-control-custom"></div>
      <div class="col-12"><button type="button" class="btn-custom btn-custom-primary">Update Password</button></div>
    </form>
  @else
    <form class="row g-3">
      <div class="col-md-6"><label class="form-label-custom">Shop Name</label><input class="form-control-custom" value="Bits & Bobbins"></div>
      <div class="col-md-6"><label class="form-label-custom">Admin Email</label><input class="form-control-custom" value="{{ $currentAdmin->email ?? 'admin@email.com' }}"></div>
      <div class="col-12"><label class="form-label-custom">Shop Address</label><textarea class="form-control-custom" rows="3"></textarea></div>
      <div class="col-12"><button type="button" class="btn-custom btn-custom-primary">Save Profile</button></div>
    </form>
  @endif
</div>
@endsection

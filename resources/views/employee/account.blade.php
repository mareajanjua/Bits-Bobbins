@extends('layouts.admin')

@section('title', 'Employee Account')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-person-gear"></i> My Account</h1>
    <p class="page-subtitle">Name and email are read-only. Employees can update profile photo and change password.</p>
  </div>
</div>
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row g-3">
  <div class="col-xl-6">
    <div class="card dashboard-overview-card h-100">
      <h2 class="card-title"><i class="bi bi-person-badge"></i> Profile</h2>
      <form method="POST" action="{{ route('employee.account.profile') }}" enctype="multipart/form-data" class="row g-3 mt-1">
        @csrf
        <div class="col-12 d-flex align-items-center gap-3">
          <img src="{{ ($employee->profile_photo ?? null) ? asset($employee->profile_photo) : asset('assets/dashboard/images/avatar.png') }}"
               alt="Employee profile photo" class="admin-account-photo">
          <div class="flex-grow-1">
            <label class="form-label-custom">Profile Photo</label>
            <input type="file" name="profile_photo" class="form-control-custom" accept="image/*">
            <p class="page-subtitle mb-0 mt-2">Default photo stays until you upload a new one.</p>
          </div>
        </div>
        <div class="col-md-6"><label class="form-label-custom">Name</label><input class="form-control-custom" value="{{ $employee->full_name }}" readonly></div>
        <div class="col-md-6"><label class="form-label-custom">Email</label><input class="form-control-custom" value="{{ $employee->email }}" readonly></div>
        <div class="col-12"><button class="btn-custom btn-custom-primary" type="submit">Update Photo</button></div>
      </form>
    </div>
  </div>
  <div class="col-xl-6">
    <div class="card dashboard-overview-card h-100">
      <h2 class="card-title"><i class="bi bi-lock"></i> Change Password</h2>
      <form method="POST" action="{{ route('employee.account.password') }}" class="row g-3 mt-1">
        @csrf
        <div class="col-12"><label class="form-label-custom">Current Password</label><input type="password" name="current_password" class="form-control-custom" required></div>
        <div class="col-md-6"><label class="form-label-custom">New Password</label><input type="password" name="password" class="form-control-custom" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="Use at least 8 characters with letters and numbers." required></div>
        <div class="col-md-6"><label class="form-label-custom">Confirm New Password</label><input type="password" name="password_confirmation" class="form-control-custom" required></div>
        <div class="col-12"><p class="page-subtitle mb-0">Password must be at least 8 characters and include letters and numbers.</p></div>
        <div class="col-12"><button class="btn-custom btn-custom-primary" type="submit">Update Password</button></div>
      </form>
    </div>
  </div>
</div>
@endsection

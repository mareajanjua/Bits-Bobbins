@extends('layouts.admin')

@section('title', 'Add Employee')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-person-badge"></i> Add Employee</h1>
    <p class="page-subtitle">Admin creates employee profile. Employee changes their own password later.</p>
  </div>
</div>

<div class="card dashboard-overview-card">
  <form method="POST" action="{{ route('admin.employees.store') }}" class="row g-3">
    @csrf
    <div class="col-md-6"><label class="form-label-custom">Full Name</label><input name="full_name" class="form-control-custom" value="{{ old('full_name') }}" pattern="[A-Za-z\s.'-]+" title="Name can contain letters and spaces only." required></div>
    <div class="col-md-6"><label class="form-label-custom">Email</label><input name="email" type="email" class="form-control-custom" value="{{ old('email') }}" required></div>
    <div class="col-md-6"><label class="form-label-custom">Phone</label><input name="phone" class="form-control-custom" value="{{ old('phone') }}"></div>
    <div class="col-md-6"><label class="form-label-custom">Temporary Password</label><input name="password" type="password" class="form-control-custom" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="Use at least 8 characters with letters and numbers." required></div>
    <div class="col-12"><p class="page-subtitle mb-0">Name cannot contain numbers. Password must include letters and numbers with at least 8 characters.</p></div>
    <div class="col-12">
      <button type="submit" class="btn-custom btn-custom-primary">Create Employee</button>
      <a href="{{ route('admin.employees.index') }}" class="btn-custom btn-custom-light">Cancel</a>
    </div>
  </form>
</div>
@endsection

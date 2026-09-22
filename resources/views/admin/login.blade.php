<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="{{ asset('assets/dashboard/libs/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/dashboard/libs/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/dashboard/css/main.css') }}">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-brand mb-4">
        <i class="bi bi-asterisk"></i>
        <span>Bits&Bobbins admin</span>
      </div>
      <h1 class="login-title">Admin Login</h1>
      <p class="login-subtitle">Sign in with the dealer account stored in the database.</p>
      @if ($errors->any())
        <div class="alert-custom alert-custom-danger">{{ $errors->first() }}</div>
      @endif
      <form method="POST" action="{{ route('admin.login.submit') }}" class="row g-3">
        @csrf
        <div class="col-12">
          <label class="form-label-custom">Email</label>
          <input type="email" name="email" class="form-control-custom" value="{{ old('email') }}" required>
        </div>
        <div class="col-12">
          <label class="form-label-custom">Password</label>
          <input type="password" name="password" class="form-control-custom" required>
        </div>
        <div class="col-12">
          <button class="btn-custom btn-custom-primary w-100" type="submit">Login</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

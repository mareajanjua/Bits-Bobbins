@extends('layouts.app')

@php
  $isLogin = $mode === 'login';
  $isRegister = $mode === 'register';
  $isForgot = $mode === 'forgot';
  $pageTitle = $isLogin ? 'Welcome Back' : ($isRegister ? 'Create Account' : 'Reset Password');
  $pageText = $isLogin
    ? 'Sign in to manage your orders, wishlist and account details.'
    : ($isRegister
      ? 'Make a home for your orders, saved addresses and little finds.'
      : 'Enter your email and we will help you get back into your account.');
@endphp

@section('title', $pageTitle)

@section('content')
<section class="bb-auth-page">
  <div class="bb-auth-shell">
    <div class="bb-auth-art" aria-hidden="true">
      <img src="{{ asset('assets/frontend/img/Home Page/login/download (55).jfif') }}" alt="">
    </div>

    <div class="bb-auth-panel">
      <div class="bb-auth-top-link">
        @if ($isLogin)
          <span>New here?</span>
          <a href="{{ route('customer.register') }}">Create an account</a>
        @elseif ($isRegister)
          <span>Already have an account?</span>
          <a href="{{ route('customer.login') }}">Sign in</a>
        @else
          <span>Remembered it?</span>
          <a href="{{ route('customer.login') }}">Back to login</a>
        @endif
      </div>

      <div class="bb-auth-copy">
        <p class="bb-auth-kicker">bits&bobbins</p>
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageText }}</p>
      </div>

      @if ($errors->any())
        <div class="store-error">{{ $errors->first() }}</div>
      @endif

      @if ($isLogin)
        <form method="POST" action="{{ route('customer.login.submit') }}" class="bb-auth-form">
          @csrf
          <label>
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
          </label>

          <label>
            <span>Role</span>
            <select name="role" required>
              <option value="customer" @selected(old('role', 'customer') === 'customer')>Customer</option>
              <option value="employee" @selected(old('role') === 'employee')>Employee</option>
            </select>
          </label>

          <label>
            <span>Password</span>
            <div class="bb-password-field">
              <input type="password" name="password" autocomplete="current-password" required>
              <button type="button" class="bb-password-toggle" aria-label="Show password" data-password-toggle>
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </label>

          <div class="bb-auth-row">
            <label class="bb-auth-check">
              <input type="checkbox" name="remember" value="1">
              <span>Remember me</span>
            </label>
            <a href="{{ route('customer.password.request') }}">Forgot password?</a>
          </div>

          <button class="bb-auth-submit" type="submit">Sign In</button>

          <p class="bb-auth-bottom">New here? <a href="{{ route('customer.register') }}">Create an account</a></p>
        </form>
      @elseif ($isRegister)
        <form method="POST" action="{{ route('customer.register.submit') }}" class="bb-auth-form">
          @csrf
          <div class="bb-auth-grid">
            <label>
              <span>First name</span>
              <input name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" pattern="[A-Za-z\s.'-]+" required>
            </label>
            <label>
              <span>Last name</span>
              <input name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" pattern="[A-Za-z\s.'-]+" required>
            </label>
          </div>

          <label>
            <span>Role</span>
            <select name="role" required>
              <option value="customer" @selected(old('role', 'customer') === 'customer')>Customer</option>
              <option value="employee" @selected(old('role') === 'employee')>Employee</option>
            </select>
          </label>

          <label>
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
          </label>

          <label>
            <span>Password</span>
            <div class="bb-password-field">
              <input type="password" name="password" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password" required>
              <button type="button" class="bb-password-toggle" aria-label="Show password" data-password-toggle>
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </label>

          <label>
            <span>Confirm password</span>
            <div class="bb-password-field">
              <input type="password" name="password_confirmation" autocomplete="new-password" required>
              <button type="button" class="bb-password-toggle" aria-label="Show password" data-password-toggle>
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </label>

          <label class="bb-auth-check">
            <input type="checkbox" name="newsletter" value="1" @checked(old('newsletter'))>
            <span>Send me product updates and new arrivals.</span>
          </label>

          <button class="bb-auth-submit" type="submit">Create Account</button>
          <p class="bb-auth-bottom">Already have an account? <a href="{{ route('customer.login') }}">Sign in</a></p>
        </form>
      @else
        <form method="POST" action="{{ route('customer.password.email') }}" class="bb-auth-form">
          @csrf
          <label>
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
          </label>
          <button class="bb-auth-submit" type="submit">Send Reset Link</button>
          <p class="bb-auth-bottom"><a href="{{ route('customer.login') }}">Back to login</a></p>
        </form>
      @endif
    </div>
  </div>
</section>

<script>
  (() => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        const input = button.closest('.bb-password-field')?.querySelector('input');
        const icon = button.querySelector('i');
        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        icon?.classList.toggle('bi-eye', !show);
        icon?.classList.toggle('bi-eye-slash', show);
      });
    });
  })();
</script>
@endsection

<!-- START: Top Navbar Component -->
    <header class="navbar-custom">
      <div class="navbar-left">
        <!-- Desktop sidebar toggle (visible on large screens only) -->
        <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3"
          id="desktop-sidebar-toggle" aria-label="Minimize Sidebar">
          <i class="bi bi-chevron-bar-left"></i>
        </button>
        <!-- Mobile sidebar toggle -->
        <button class="sidebar-toggle-btn me-2" id="sidebar-toggle" aria-label="Toggle Navigation">
          <i class="bi bi-list"></i>
        </button>

        @if (! session('employee_id'))
        <!-- Quick Actions Dropdown -->
        <div class="dropdown ms-2">
          <button class="btn-quick-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
            id="quick-actions-dropdown">
            <i class="bi bi-plus-lg"></i>
            <span>Create</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-quick-action" aria-labelledby="quick-actions-dropdown">
            <li class="dropdown-header">Quick Action Shortcuts</li>
            <li><a class="dropdown-item" href="{{ route('admin.products.create') }}"><i class="bi bi-box-seam"></i> Add Product</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.employees.create') }}"><i class="bi bi-person-badge"></i> Add Employee</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.faq.index') }}"><i class="bi bi-question-circle"></i> Add FAQ</a></li>
          </ul>
        </div>
        @endif
      </div>

      <!-- Mid navbar: search pill -->
      <form class="navbar-search-wrapper" method="GET" action="{{ session('employee_id') ? route('employee.orders') : route('admin.search') }}">
        <input type="text" class="navbar-search-input"
          name="q"
          value="{{ request('q', request('search')) }}"
          placeholder="{{ session('employee_id') ? 'Search orders, customers, products...' : 'Search products, IDs, categories, orders, customers...' }}"
          id="main-search">
        <button class="navbar-search-btn" type="submit" aria-label="Search">
          <i class="bi bi-search"></i>
        </button>
      </form>

      <!-- Right actions -->
      <div class="navbar-actions">
        <!-- Fullscreen Toggle -->
        <button class="navbar-action-btn me-1" aria-label="Toggle Fullscreen" id="btn-fullscreen">
          <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <div class="dropdown">
          <button class="navbar-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="btn-notifications" data-bs-auto-close="outside">
            <i class="bi bi-bell"></i>
            @if (($adminNotificationCount ?? 0) > 0)
              <span class="navbar-action-badge"></span>
            @endif
          </button>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-notification p-0"
            aria-labelledby="btn-notifications">
            <div class="notification-header">
              <h6 class="notification-title">Notifications</h6>
              <button class="btn-clear-all" type="button">Mark all read</button>
            </div>
            <div class="notification-list">
              @forelse (($adminNotifications ?? collect()) as $notification)
                <a href="{{ $notification['url'] }}" class="notification-item">
                  <div class="notification-icon {{ $notification['tone'] }}">
                    <i class="bi {{ $notification['icon'] }}"></i>
                  </div>
                  <div class="notification-content">
                    <p class="notification-text">{{ $notification['label'] }}: <strong>{{ $notification['count'] }}</strong></p>
                    <span class="notification-time">Updated from database</span>
                  </div>
                  <span class="notification-unread-dot"></span>
                </a>
              @empty
                <div class="notification-item">
                  <div class="notification-content">
                    <p class="notification-text mb-0">No notifications requiring attention.</p>
                  </div>
                </div>
              @endforelse
            </div>
            <a href="{{ session('employee_id') ? route('employee.dashboard') : route('admin.dashboard') }}" class="notification-footer">View Dashboard</a>
          </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown ms-2">
          <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="profile-dropdown">
            <img src="{{ $currentAdmin && $currentAdmin->profile_photo ? asset($currentAdmin->profile_photo) : asset('assets/dashboard/images/avatar.png') }}" alt="Profile Image" class="navbar-profile-img">
            <span class="navbar-profile-name d-none d-md-inline">{{ $currentAdmin->username ?? 'Administrator' }}</span>
            <i class="bi bi-chevron-down navbar-profile-caret"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
            <li class="dropdown-header">Welcome !</li>
            @if (! session('employee_id'))
              <li><a class="dropdown-item" href="{{ route('admin.account') }}"><i class="bi bi-person"></i> My Account</a></li>
              <li><a class="dropdown-item" href="{{ route('admin.account') }}"><i class="bi bi-lock"></i> Change Password</a></li>
            @else
              <li><a class="dropdown-item" href="{{ route('employee.account') }}"><i class="bi bi-person-badge"></i> My Account</a></li>
            @endif
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <form method="POST" action="{{ session('employee_id') ? route('customer.logout') : route('admin.logout') }}">
                @csrf
                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <!-- END: Top Navbar Component -->

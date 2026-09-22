<div class="sidebar-wrapper" id="sidebar">
    <!-- Brand Logo / Identity -->
    <a href="{{ session('employee_id') ? route('employee.dashboard') : route('admin.dashboard') }}" class="sidebar-brand">
      <i class="bi bi-asterisk"></i>
      <span>Bits&Bobbins</span>
    </a>

    <!-- Navigation Menu -->
    <div class="flex-grow-1 overflow-y-auto" id="admin-sidebar-menu">
      <!-- Group: Main -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Main</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="{{ session('employee_id') ? route('employee.dashboard') : route('admin.dashboard') }}" class="sidebar-menu-link" id="menu-dashboard" title="Dashboard">
              <i class="bi bi-grid-fill"></i>
              <span>Dashboard</span>
            </a>
          </li>
        </ul>
      </div>

      @if (session('employee_id'))
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Orders</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="#sidebar-employee-orders" class="sidebar-menu-link" title="Orders"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-employee-orders">
              <i class="bi bi-clipboard-check"></i>
              <span>Orders</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-employee-orders" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('employee.orders') }}" class="sidebar-submenu-link">All Orders</a></li>
            </ul>
          </li>
        </ul>
      </div>

      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Delivery Management</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="#sidebar-employee-delivery" class="sidebar-menu-link" title="Delivery Management"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-employee-delivery">
              <i class="bi bi-truck"></i>
              <span>Delivery Management</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-employee-delivery" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('employee.delivery', 'pending') }}" class="sidebar-submenu-link">Pending Dispatch</a></li>
              <li><a href="{{ route('employee.delivery', 'dispatched') }}" class="sidebar-submenu-link">Dispatched / In Transit</a></li>
              <li><a href="{{ route('employee.delivery', 'delivered') }}" class="sidebar-submenu-link">Delivered</a></li>
              <li><a href="{{ route('employee.reports') }}" class="sidebar-submenu-link">Delivery Reports</a></li>
            </ul>
          </li>
        </ul>
      </div>

      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Account</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="{{ route('employee.account') }}" class="sidebar-menu-link" title="My Account">
              <i class="bi bi-person-gear"></i>
              <span>My Account</span>
            </a>
          </li>
        </ul>
      </div>
      @else

      <!-- Group: Management -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Management</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="#sidebar-products" class="sidebar-menu-link" id="menu-products" title="Products"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-products">
              <i class="bi bi-box-seam"></i>
              <span>Products</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-products" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.products.index') }}" class="sidebar-submenu-link">All Products</a></li>
              <li><a href="{{ route('admin.product-details.index') }}" class="sidebar-submenu-link">Product Details</a></li>
              <li><a href="{{ route('admin.products.create') }}" class="sidebar-submenu-link">Add New Product</a></li>
              <li><a href="{{ route('admin.categories.index') }}" class="sidebar-submenu-link">Categories</a></li>
              <li><a href="{{ route('admin.categories.index') }}#subcategories" class="sidebar-submenu-link">Subcategories</a></li>
            </ul>
          </li>

          <li class="sidebar-menu-item">
            <a href="#sidebar-stock" class="sidebar-menu-link" id="menu-stock" title="Stock / Inventory"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-stock">
              <i class="bi bi-boxes"></i>
              <span>Stock / Inventory</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-stock" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.stock.index') }}" class="sidebar-submenu-link">Stock Levels</a></li>
              <li><a href="{{ route('admin.stock.low') }}" class="sidebar-submenu-link">Low / Out of Stock</a></li>
            </ul>
          </li>

          <li class="sidebar-menu-item">
            <a href="#sidebar-orders" class="sidebar-menu-link" id="menu-orders" title="Orders"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-orders">
              <i class="bi bi-clipboard-check"></i>
              <span>Orders</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-orders" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.orders.index') }}" class="sidebar-submenu-link">All Orders</a></li>
              <li><a href="{{ route('admin.orders.delivery') }}" class="sidebar-submenu-link">By Delivery Type</a></li>
            </ul>
          </li>

          <li class="sidebar-menu-item">
            <a href="#sidebar-employees" class="sidebar-menu-link" id="menu-employees" title="Employees"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-employees">
              <i class="bi bi-person-badge"></i>
              <span>Employees</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-employees" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.employees.index') }}" class="sidebar-submenu-link">All Employees</a></li>
              <li><a href="{{ route('admin.employees.create') }}" class="sidebar-submenu-link">Add Employee</a></li>
            </ul>
          </li>

          <li class="sidebar-menu-item">
            <a href="#sidebar-customers" class="sidebar-menu-link" id="menu-customers" title="Customers"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-customers">
              <i class="bi bi-people"></i>
              <span>Customers</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-customers" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.customers.index') }}" class="sidebar-submenu-link">Registered Customers</a></li>
              <li><a href="{{ route('admin.customers.deactivated') }}" class="sidebar-submenu-link">Deactivated Accounts</a></li>
            </ul>
          </li>

          <li class="sidebar-menu-item">
            <a href="#sidebar-payments" class="sidebar-menu-link" id="menu-payments" title="Payments"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-payments">
              <i class="bi bi-credit-card"></i>
              <span>Payments</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-payments" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.payments.method', 'credit_card') }}" class="sidebar-submenu-link">Credit Card</a></li>
              <li><a href="{{ route('admin.payments.method', 'cheque') }}" class="sidebar-submenu-link">Cheque</a></li>
              <li><a href="{{ route('admin.payments.method', 'vpp_cod') }}" class="sidebar-submenu-link">VPP / Cash on Delivery</a></li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- Group: Customer & Service -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Customer & Service</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="{{ route('admin.returns.index') }}" class="sidebar-menu-link" id="menu-returns" title="Returns & Replacements">
              <i class="bi bi-arrow-counterclockwise"></i>
              <span>Returns & Replacements</span>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="{{ route('admin.warranty.index') }}" class="sidebar-menu-link" id="menu-warranty" title="Warranty Records">
              <i class="bi bi-shield-check"></i>
              <span>Warranty Records</span>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="{{ route('admin.feedback.index') }}" class="sidebar-menu-link" id="menu-feedback" title="Feedback">
              <i class="bi bi-chat-square-heart"></i>
              <span>Feedback</span>
            </a>
          </li>
          <li class="sidebar-menu-item">
            <a href="{{ route('admin.faq.index') }}" class="sidebar-menu-link" id="menu-faq" title="FAQ Management">
              <i class="bi bi-question-circle"></i>
              <span>FAQ Management</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Group: System -->
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">System</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="#sidebar-settings" class="sidebar-menu-link" id="menu-settings" title="Settings"
              data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebar-settings">
              <i class="bi bi-gear"></i>
              <span>Settings</span>
              <i class="bi bi-chevron-down dropdown-caret"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="sidebar-settings" data-bs-parent="#admin-sidebar-menu">
              <li><a href="{{ route('admin.settings', 'profile') }}" class="sidebar-submenu-link">Shop Profile</a></li>
              <li><a href="{{ route('admin.settings', 'password') }}" class="sidebar-submenu-link">Change Password</a></li>
            </ul>
          </li>
        </ul>
      </div>
      @endif
    </div>

    <!-- Sidebar Profile Card (Dynamic Footer) -->
    <div class="dropup sidebar-profile-dropup">
      <button class="sidebar-profile dropdown-toggle" type="button" id="sidebar-profile-menu"
        data-bs-toggle="dropdown" aria-expanded="false">
        <img src="{{ $currentAdmin && $currentAdmin->profile_photo ? asset($currentAdmin->profile_photo) : asset('assets/dashboard/images/avatar.png') }}" alt="{{ $currentAdmin->username ?? 'Admin' }}" class="sidebar-profile-img"
          onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=256&auto=format&fit=crop'">
        <span class="sidebar-profile-info">
          <span class="sidebar-profile-name">{{ $currentAdmin->username ?? 'Administrator' }}</span>
          <span class="sidebar-profile-email">{{ $currentAdmin->email ?? 'admin@email.com' }}</span>
        </span>
        <i class="bi bi-chevron-up sidebar-profile-caret"></i>
      </button>
      <ul class="dropdown-menu sidebar-profile-menu" aria-labelledby="sidebar-profile-menu">
        @if (! session('employee_id'))
          <li><a class="dropdown-item" href="{{ route('admin.account') }}"><i class="bi bi-person"></i> My Account</a></li>
          <li><a class="dropdown-item" href="{{ route('admin.account') }}"><i class="bi bi-lock"></i> Change Password</a></li>
        @else
          <li><a class="dropdown-item" href="{{ route('employee.account') }}"><i class="bi bi-person"></i> My Account</a></li>
        @endif
        <li>
          <form method="POST" action="{{ session('employee_id') ? route('customer.logout') : route('admin.logout') }}">
            @csrf
            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
  <!-- ==========================================
         END: Sidebar Component
         ========================================== -->


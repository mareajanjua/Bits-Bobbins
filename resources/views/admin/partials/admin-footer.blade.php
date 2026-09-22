<!-- START: Footer Component -->
    <div class="footer-custom" role="contentinfo">
      <div class="footer-left">
        <span class="footer-logo">
          <i class="bi bi-asterisk"></i> Bits&Bobbins {{ session('employee_id') ? 'employee' : 'admin' }}
        </span>
        <span class="footer-separator">|</span>
        <span class="footer-copy">&copy; 2026 by SoloDesignStudio</span>
      </div>
      <div class="footer-right">
        <ul class="footer-links">
          @if (session('employee_id'))
            <li><a href="{{ route('employee.dashboard') }}" class="footer-link">Dashboard</a></li>
            <li><a href="{{ route('employee.orders') }}" class="footer-link">Orders</a></li>
            <li><a href="{{ route('employee.reports') }}" class="footer-link">Delivery Reports</a></li>
            <li><a href="{{ route('employee.account') }}" class="footer-link">My Account</a></li>
          @else
            <li><a href="{{ route('admin.dashboard') }}" class="footer-link">Dashboard</a></li>
            <li><a href="{{ route('admin.orders.index') }}" class="footer-link">Orders</a></li>
            <li><a href="{{ route('admin.products.index') }}" class="footer-link">Products</a></li>
            <li><a href="{{ route('admin.account') }}" class="footer-link">My Account</a></li>
          @endif
        </ul>
      </div>
    </div>
    <!-- END: Footer Component -->

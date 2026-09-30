        <!-- Navbar Start -->
        @php
            $visitorProfile = $customer ?: ($employee ?: $admin);
            $visitorAccountRoute = $customer
                ? route('customer.account')
                : ($employee ? route('employee.account') : ($admin ? route('admin.account') : route('customer.login')));
        @endphp
        <nav class="navbar navbar-expand-lg navbar-light sticky-top px-4 px-lg-5 py-lg-0 bb-navbar">
            <div class="bb-navbar-inner">
            <a href="{{ route('store.home') }}" class="navbar-brand">
                <h1 class="m-0">Bits&Bobbins</h1>
            </a>
            <div class="d-flex d-lg-none ms-auto me-2 bb-mobile-icons">
                <a href="{{ route('store.products') }}" class="bb-icon-btn" aria-label="Search"><i class="fa fa-search"></i></a>
                <a href="{{ $visitorAccountRoute }}" class="bb-icon-btn bb-account-avatar-link" aria-label="Account">
                    @if ($visitorProfile && !empty($visitorProfile->profile_photo))
                        <img src="{{ asset($visitorProfile->profile_photo) }}" alt="Account" class="bb-account-avatar">
                    @else
                        <i class="fa fa-user"></i>
                    @endif
                </a>
                <button type="button" class="bb-menu-open bb-mobile-menu-open" aria-label="Open menu" aria-controls="bbFullMenu" aria-expanded="false">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav bb-navbar-left">
                    <a href="{{ route('store.home') }}" class="nav-item nav-link active">Home</a>
                    <div class="nav-item dropdown">
                        <a href="{{ route('store.products') }}" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Shop</a>
                        <div class="dropdown-menu rounded-0 rounded-bottom border-0 shadow-sm m-0 bb-shop-menu">
                            <a href="{{ route('store.products') }}" class="dropdown-item">All Products</a>
                            @foreach ($frontendCategories as $category)
                                @php($categorySubs = $frontendSubcategories->get($category->category_code, collect()))
                                @if ($categorySubs->isNotEmpty())
                                    <div class="bb-category-with-submenu">
                                        <button type="button" class="dropdown-item bb-category-accordion-toggle" aria-expanded="false">
                                            <span>{{ $category->category_name }}</span>
                                            <i class="fa fa-chevron-down"></i>
                                        </button>
                                        <div class="bb-category-submenu" hidden>
                                            <a href="{{ route('store.products', ['category' => $category->category_code]) }}" class="dropdown-item">All {{ $category->category_name }}</a>
                                            @foreach ($categorySubs as $subcategory)
                                                <a href="{{ route('store.products', ['category' => $category->category_code, 'subcategory' => $subcategory->subcategory_id]) }}" class="dropdown-item">{{ $subcategory->subcategory_name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('store.products', ['category' => $category->category_code]) }}" class="dropdown-item">{{ $category->category_name }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('store.about') }}#about-brand" class="nav-item nav-link">About</a>
                    <a href="{{ route('store.faq') }}" class="nav-item nav-link">FAQ</a>
                    <a href="{{ route('store.about') }}#about-contact" class="nav-item nav-link">Contact</a>
                </div>
                <div class="d-none d-lg-flex align-items-center gap-4 bb-navbar-right ms-auto">
                    <div class="dropdown">
                        <button class="bb-nav-auth-link border-0 bg-transparent d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @if ($visitorProfile && !empty($visitorProfile->profile_photo))
                                <img src="{{ asset($visitorProfile->profile_photo) }}" alt="Account" class="bb-account-avatar">
                            @else
                                <i class="fa fa-user"></i>
                            @endif
                            <span>Account</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end rounded-0 rounded-bottom border-0 shadow-sm m-0 bb-shop-menu">
                            @if ($admin)
                                <a class="dropdown-item" href="{{ route('admin.account') }}"><i class="bi bi-person me-2"></i>My Account</a>
                                <a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="bi bi-gear me-2"></i>Settings</a>
                                <form method="POST" action="{{ route('customer.logout') }}">@csrf<button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
                            @elseif ($employee)
                                <a class="dropdown-item" href="{{ route('employee.dashboard') }}"><i class="bi bi-grid me-2"></i>Go to Dashboard</a>
                                <a class="dropdown-item" href="{{ route('employee.account') }}"><i class="bi bi-person me-2"></i>My Account</a>
                                <form method="POST" action="{{ route('customer.logout') }}">@csrf<button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
                            @elseif ($customer)
                                <a class="dropdown-item" href="{{ route('customer.account') }}"><i class="bi bi-person me-2"></i>My Account</a>
                                <a class="dropdown-item" href="{{ route('customer.orders') }}"><i class="bi bi-bag-check me-2"></i>My Orders</a>
                                <a class="dropdown-item" href="{{ route('customer.feedback') }}"><i class="bi bi-chat-square-heart me-2"></i>Feedback</a>
                                <form method="POST" action="{{ route('customer.logout') }}">@csrf<button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
                            @else
                                <a class="dropdown-item" href="{{ route('customer.login') }}">Login</a>
                                <a class="dropdown-item" href="{{ route('customer.register') }}">Sign Up</a>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="bb-menu-open" aria-label="Open menu" aria-controls="bbFullMenu" aria-expanded="false">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>
            </div>
            </div>
        </nav>
        <!-- Navbar End -->
        <div class="bb-full-menu" id="bbFullMenu" aria-hidden="true">
            <div class="bb-full-menu-inner">
                <div class="bb-full-menu-top">
                    <button type="button" class="bb-menu-close" aria-label="Back">
                        <i class="fa fa-arrow-left"></i>
                    </button>
                    <a href="{{ route('store.home') }}" class="bb-full-menu-brand">Bits&Bobbins</a>
                    <span class="bb-full-menu-top-spacer" aria-hidden="true"></span>
                </div>
                <form class="bb-full-menu-search" method="GET" action="{{ route('store.products') }}" role="search">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products..." aria-label="Search products">
                    <button type="submit" aria-label="Submit search"><i class="fa fa-search"></i></button>
                </form>
                <div class="bb-full-menu-grid">
                    <nav class="bb-full-menu-main" aria-label="Main menu">
                        <a href="{{ route('store.home') }}">Home</a>
                        <a href="{{ route('store.products') }}">Shop</a>
                        <a href="{{ route('store.about') }}#about-brand">About</a>
                        <a href="{{ route('store.faq') }}">FAQ</a>
                        <a href="{{ route('store.about') }}#about-contact">Contact</a>
                    </nav>
                    <div class="bb-full-menu-group">
                        <h3>Shop</h3>
                        <div class="bb-full-menu-list">
                            <a href="{{ route('store.products') }}">All Products</a>
                            @foreach ($frontendCategories as $category)
                                @php($categorySubs = $frontendSubcategories->get($category->category_code, collect()))
                                @if ($categorySubs->isNotEmpty())
                                    <div class="bb-full-menu-category">
                                        <button type="button" class="bb-full-menu-category-toggle" aria-expanded="false">
                                            <span>{{ $category->category_name }}</span>
                                            <i class="fa fa-chevron-down"></i>
                                        </button>
                                        <div class="bb-full-menu-sublist" hidden>
                                            <a href="{{ route('store.products', ['category' => $category->category_code]) }}">All {{ $category->category_name }}</a>
                                            @foreach ($categorySubs as $subcategory)
                                                <a href="{{ route('store.products', ['category' => $category->category_code, 'subcategory' => $subcategory->subcategory_id]) }}">{{ $subcategory->subcategory_name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('store.products', ['category' => $category->category_code]) }}">{{ $category->category_name }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

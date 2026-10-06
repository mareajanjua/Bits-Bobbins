@php
    $cartCount = collect(session('cart', []))->sum();
    $customer = session('customer_id') ? DB::table('customer')->where('customer_id', session('customer_id'))->first() : null;
    $admin = session('admin_id') ? DB::table('admin')->where('admin_id', session('admin_id'))->first() : null;
    $employee = session('employee_id') ? DB::table('employee')->where('employee_id', session('employee_id'))->first() : null;
    $frontendCategories = DB::table('category')->orderBy('category_name')->get();
    $frontendSubcategories = DB::table('subcategory')->orderBy('subcategory_name')->get()->groupBy('category_code');
    $latestProducts = DB::table('product')
        ->join('category', 'product.category_code', '=', 'category.category_code')
        ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
        ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
        ->where('product.is_active', 1)
        ->select('product.*', 'category.category_name', 'subcategory.subcategory_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
        ->orderByDesc('product.created_at')
        ->limit(4)
        ->get();
    $feedbackPreview = DB::table('feedback')
        ->join('customer', 'feedback.customer_id', '=', 'customer.customer_id')
        ->select('customer.full_name', 'feedback.rating', 'feedback.message', 'feedback.submitted_at')
        ->orderByDesc('feedback.submitted_at')
        ->limit(8)
        ->get();
    $feedbackSlides = $feedbackPreview->values();
    $faqPreview = DB::table('faq')
        ->orderBy('display_order')
        ->get();
    $categoryImages = [
        'art&craft' => 'assets/frontend/img/Home Page/Category/Art&Craft.jpg',
        'art & craft' => 'assets/frontend/img/Home Page/Category/Art&Craft.jpg',
        'bags&wallets' => 'assets/frontend/img/Home Page/Category/Bags&Wallets.jpg',
        'bags & wallets' => 'assets/frontend/img/Home Page/Category/Bags&Wallets.jpg',
        'beauty & skincare' => 'assets/frontend/img/Home Page/Category/Beauty & Skincare.jpg',
        'beauty / accessories' => 'assets/frontend/img/Home Page/Category/Beauty & Skincare.jpg',
        'dolls & accessories' => 'assets/frontend/img/Home Page/Category/Dolls & Accessories.jpg',
        'dolls' => 'assets/frontend/img/Home Page/Category/Dolls & Accessories.jpg',
        'gifts&stationary' => 'assets/frontend/img/Home Page/Category/Gifts&Stationary.jpg',
        'gift articles' => 'assets/frontend/img/Home Page/Category/Gifts&Stationary.jpg',
        'stationery / files' => 'assets/frontend/img/Home Page/Category/Gifts&Stationary.jpg',
        'kids (general & lifestyle)' => 'assets/frontend/img/Home Page/Category/Kids (General & Lifestyle).jpg',
    ];
    $heroImages = [
        'assets/frontend/img/Home Page/Hero/beauty.jpg',
        'assets/frontend/img/Home Page/Hero/Dollhouses.jpg',
        'assets/frontend/img/Home Page/Hero/gifts&accessories.jpg',
        'assets/frontend/img/Home Page/Hero/Kids&general.jpg',
        'assets/frontend/img/Home Page/Hero/wallets&bags.jpg',
    ];
    $homeCart = session('cart', []);
    $homeCartItems = collect();
    if ($homeCart) {
        $cartProducts = DB::table('product')
            ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
            ->whereIn('product.product_id', array_keys($homeCart))
            ->select('product.*', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'))
            ->get()
            ->keyBy('product_id');
        $homeCartItems = collect($homeCart)->map(function ($quantity, $productId) use ($cartProducts) {
            $product = $cartProducts->get($productId);
            return $product ? (object) [
                'product' => $product,
                'quantity' => (int) $quantity,
                'line_total' => (int) $quantity * (float) $product->price,
            ] : null;
        })->filter()->values();
    }
    $homeCartSubtotal = $homeCartItems->sum('line_total');
    $shouldOpenCart = false;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Bits&Bobbins')</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    <base href="{{ asset('assets/frontend') }}/">

    <!-- Favicon -->
    <link href="{{ asset('assets/bits-bobbins-favicon.jfif') }}" rel="icon" type="image/jpeg">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&family=Lobster+Two:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('assets/dashboard/libs/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('assets/frontend/lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/frontend/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('assets/frontend/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('assets/frontend/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/store/store.css') }}?v={{ filemtime(public_path('assets/store/store.css')) }}">

    <style>
        @font-face {
            font-family: "Porcelain";
            src: url("{{ asset('assets/frontend/fonts/Porcelain.ttf') }}") format("truetype");
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: "Porcelain";
            src: url("{{ asset('assets/frontend/fonts/Porcelain.ttf') }}") format("truetype");
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        body,
        button,
        input,
        select,
        textarea {
            font-family: "Heebo", sans-serif;
            font-weight: 400;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .navbar-brand h1,
        .bb-footer-brand,
        .bb-full-menu-brand,
        .bb-community-logo {
            font-family: "Porcelain", cursive;
            font-weight: 700;
        }

        .bb-shop-menu .bb-category-with-submenu {
            display: grid;
            gap: 0;
        }

        .bb-category-accordion-toggle,
        .bb-full-menu-category-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            color: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .85rem;
            text-align: left;
            font: inherit;
            font-weight: 400;
        }

        .bb-category-accordion-toggle {
            color: #111;
        }

        .bb-category-accordion-toggle:hover,
        .bb-category-accordion-toggle:focus {
            background: #5E442B;
            color: #fff;
        }

        .bb-category-accordion-toggle i,
        .bb-full-menu-category-toggle i {
            transition: transform .16s ease;
        }

        .bb-category-with-submenu.is-open .bb-category-accordion-toggle i,
        .bb-full-menu-category.is-open .bb-full-menu-category-toggle i {
            transform: rotate(180deg);
        }

        .bb-category-submenu,
        .bb-full-menu-sublist {
            display: grid;
            gap: 0;
        }

        .bb-category-submenu {
            padding: .2rem 0 .35rem .75rem;
            background: #fff;
        }

        .bb-category-submenu .dropdown-item {
            font-size: .95rem;
            color: #111;
        }

        .bb-full-menu-category {
            display: grid;
            gap: .45rem;
        }

        .bb-full-menu-category-toggle {
            padding: 0;
            color: #fff;
            font-size: clamp(1rem, 1.7vw, 1.25rem);
        }

        .bb-full-menu-sublist {
            gap: .45rem;
            padding-left: 1rem;
        }

        .bb-full-menu-sublist a {
            color: rgba(255, 255, 255, .82);
            font-size: clamp(.95rem, 1.4vw, 1.08rem);
            font-weight: 400;
        }

        .bb-shop-menu {
            min-width: 215px;
            border: 3px solid #111 !important;
            border-radius: 16px !important;
            margin-top: .85rem !important;
            box-shadow: 0 7px 0 rgba(17, 17, 17, .16);
        }

        .bb-nav-auth-link {
            color: #111;
            font-weight: 400;
            text-decoration: none;
            padding: .45rem .25rem;
            white-space: nowrap;
        }

        .bb-nav-auth-link:hover {
            color: #5E442B;
        }
        body {
            background-color: #fff;
            background-image:
                linear-gradient(rgba(94, 68, 43, .08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(94, 68, 43, .08) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .bb-section {
            padding: 3rem 0;
            background-color: #fff;
            background-image:
                linear-gradient(rgba(94, 68, 43, .08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(94, 68, 43, .08) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .bb-section-soft {
            background-color: #fff;
        }

        .bb-hero-final {
            position: relative;
            overflow: hidden;
            padding: clamp(3.5rem, 7vw, 5.5rem) 0 3rem;
            background-color: #fff;
            background-image:
                linear-gradient(rgba(94, 68, 43, .08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(94, 68, 43, .08) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .bb-hero-final-head {
            text-align: center;
            max-width: 980px;
            margin: 0 auto clamp(2.3rem, 5vw, 4rem);
        }

        .bb-hero-final-title {
            font-family: "Porcelain", cursive;
            color: #111;
            font-size: clamp(4rem, 9vw, 9rem);
            line-height: .78;
            font-weight: 700;
            margin: 0;
            position: relative;
            display: inline-block;
            padding-top: 1.2rem;
            text-shadow: .8px 0 #111, 0 .8px #111;
        }

        .bb-hero-title-word {
            position: relative;
            display: inline-block;
            padding-top: 1.2rem;
        }

        .bb-hero-title-word .bb-category-sticker {
            left: 18%;
            top: -.1rem;
            transform: rotate(-5deg);
            white-space: nowrap;
        }

        .bb-hero-final-copy {
            color: #1f1c17;
            font-weight: 400;
            font-size: clamp(1rem, 1.35vw, 1.22rem);
            max-width: 760px;
            margin: 1.15rem auto 0;
        }

        .bb-hero-collage {
            position: relative;
            max-width: 980px;
            margin: 0 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 1.2rem 0 2.4rem;
        }

        .bb-hero-photo {
            position: relative;
            width: clamp(150px, 15vw, 220px);
            aspect-ratio: .92 / 1;
            border: 3px solid #1f1c17;
            border-radius: 22px;
            background: transparent;
            overflow: hidden;
            box-shadow: 0 10px 18px rgba(31, 28, 23, .06);
            margin-left: clamp(-42px, -3.6vw, -24px);
        }

        .bb-hero-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .bb-hero-photo-0 img,
        .bb-hero-photo-4 img {
            transform: scale(1.34);
        }

        .bb-hero-photo-0 img {
            object-position: 68% 68%;
        }

        .bb-hero-photo-4 img {
            object-position: 26% 68%;
        }

        .bb-hero-photo-0 { margin-left: 0; transform: translateY(22px) rotate(-3deg); z-index: 1; }
        .bb-hero-photo-1 { transform: translateY(-10px) rotate(3deg); z-index: 3; }
        .bb-hero-photo-2 { width: clamp(175px, 17vw, 260px); aspect-ratio: .86 / 1.08; transform: translateY(10px) rotate(-1deg); z-index: 5; }
        .bb-hero-photo-3 { transform: translateY(-18px) rotate(2deg); z-index: 4; }
        .bb-hero-photo-4 { transform: translateY(18px) rotate(3deg); z-index: 2; }

        .bb-hero-face {
            position: absolute;
            right: 17%;
            bottom: 4%;
            width: clamp(82px, 9vw, 124px);
            z-index: 9;
            filter: drop-shadow(3px 5px 0 rgba(31, 28, 23, .24));
        }

        .bb-hero-spark {
            display: none !important;
        }

        .bb-hero-spark-one { left: 10%; top: 22%; transform: rotate(-10deg); }
        .bb-hero-spark-two { right: 31%; top: 13%; transform: rotate(12deg); }

        .bb-hero-actions {
            display: flex;
            justify-content: center;
            margin-top: .7rem;
        }

        .bb-section-title {
            font-family: "Porcelain", cursive;
            color: #5E442B;
            font-weight: 700;
            font-size: clamp(3rem, 6vw, 5.4rem);
            line-height: .95;
            margin-bottom: .75rem;
        }

        .bb-section-copy {
            color: #6d7f75;
            max-width: 680px;
            margin: 0 auto 2rem;
            font-size: 1.05rem;
        }

        .bb-category-card,
        .bb-product-card,
        .bb-feature-card,
        .bb-preview-card,
        .bb-search-panel,
        .bb-cta-panel {
            border: 1px solid rgba(94, 68, 43, .22);
            background: rgba(255, 255, 255, .86);
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(94, 68, 43, .08);
        }

        .bb-category-card {
            width: 100%;
            height: 100%;
            min-height: 0;
            padding: .9rem .9rem 1rem;
            color: #111;
            display: grid;
            grid-template-rows: auto auto;
            gap: .9rem;
            text-decoration: none;
            border: 3px solid #1f1c17;
            border-radius: 18px;
            background: #fff;
            box-shadow: 7px 8px 0 rgba(31, 28, 23, .16);
        }

        .bb-category-card:hover,
        .bb-product-card:hover {
            transform: translateY(-3px);
            color: #111;
            box-shadow: 8px 10px 0 rgba(31, 28, 23, .22), 0 20px 40px rgba(94, 68, 43, .12);
        }

        .bb-category-image {
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 13px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .bb-category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .bb-category-card i,
        .bb-feature-card i {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: #fff;
            color: #5E442B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .bb-category-card h3 {
            color: #111;
            font-family: "Heebo", sans-serif;
            font-weight: 400;
            text-align: center;
            font-size: clamp(1.2rem, 1.6vw, 1.65rem);
            line-height: 1.05;
            margin: 0;
        }

        .bb-category-slider {
            display: flex;
            gap: clamp(1.2rem, 2.5vw, 2.25rem);
            overflow-x: auto;
            padding: .65rem .35rem 1.35rem;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            justify-content: safe center;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .bb-category-slider::-webkit-scrollbar {
            display: none;
        }

        .bb-category-carousel-wrap {
            position: relative;
        }

        .bb-category-slide {
            flex: 0 0 clamp(220px, 21vw, 300px);
            scroll-snap-align: start;
            display: flex;
        }

        .bb-category-cta-row {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .bb-category-cta {
            border: 3px solid #1f1c17;
            color: #1f1c17;
            background: transparent;
            padding: .55rem 1.35rem;
            min-height: 42px;
            font-size: .9rem;
            border-radius: 999px;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .28);
            transition: none;
        }

        .bb-category-cta:hover {
            color: #1f1c17;
            background: transparent;
            transform: none;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .28);
        }

        .bb-section-eyebrow {
            display: block;
            color: #111;
            font-weight: 400;
            margin: .65rem auto 2.2rem;
            max-width: 640px;
        }

        .bb-product-card {
            width: 100%;
            height: 100%;
            min-height: 430px;
            overflow: hidden;
            transition: .22s ease;
            border: 3px solid #1f1c17;
            border-radius: 24px;
            background: #fff;
            box-shadow: 8px 10px 0 rgba(31, 28, 23, .18), 0 18px 34px rgba(31, 28, 23, .14);
            display: grid;
            grid-template-rows: auto 1fr;
            padding: .9rem;
            cursor: pointer;
            position: relative;
        }

        .bb-product-card-link {
            position: absolute;
            inset: 0;
            z-index: 2;
            border-radius: 21px;
        }

        .bb-product-image {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            border-radius: 18px;
            transition: .22s ease;
            position: relative;
        }

        .bb-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .bb-product-hover-img {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity .22s ease;
        }

        .bb-product-card:hover .bb-product-hover-img {
            opacity: 1;
        }

        .bb-product-image i {
            color: #5E442B;
            font-size: 3rem;
        }

        .bb-product-body {
            padding: .75rem .2rem .1rem;
            display: grid;
            gap: .85rem;
            align-content: end;
            position: relative;
            min-height: 145px;
        }

        .bb-product-body form {
            position: relative;
            z-index: 3;
        }

        .bb-product-info-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .8rem;
            align-items: end;
        }

        .bb-product-body h3,
        .bb-preview-card h3,
        .bb-search-panel h3 {
            color: #111;
            font-family: "Heebo", sans-serif;
            font-weight: 400;
            line-height: 1.08;
        }

        .bb-product-meta {
            color: #1f1c17;
            font-size: .88rem;
            font-weight: 400;
        }

        .bb-price {
            color: #111;
            font-weight: 400;
            font-size: 1.05rem;
            white-space: nowrap;
        }

        .bb-stock-pill {
            background: transparent;
            color: #1f1c17;
            font-weight: 400;
            font-size: .95rem;
        }

        .bb-stock-pill.low {
            background: transparent;
            color: #1f1c17;
        }

        .bb-stock-pill.out {
            background: transparent;
            color: #1f1c17;
        }

        .bb-product-buy-btn {
            width: 100%;
            border: 3px solid #1f1c17;
            border-radius: 999px;
            background: #5E442B;
            color: #fff;
            min-height: 44px;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            box-shadow: 3px 5px 0 rgba(31, 28, 23, .26);
            transition: .22s ease;
        }

        .bb-product-buy-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .bb-product-card:hover {
            background: #5E442B;
            color: #fff;
            box-shadow: 9px 11px 0 rgba(31, 28, 23, .25), 0 22px 45px rgba(31, 28, 23, .22);
        }

        .bb-product-card:hover .bb-product-image {
            background: #fff;
        }

        .bb-product-card:hover .bb-product-body h3,
        .bb-product-card:hover .bb-product-meta,
        .bb-product-card:hover .bb-price,
        .bb-product-card:hover .bb-stock-pill {
            color: #fff;
        }

        .bb-product-card:hover .bb-product-buy-btn {
            background: #fff;
            color: #5E442B;
            border-color: #1f1c17;
            box-shadow: 3px 5px 0 rgba(31, 28, 23, .3);
        }

        .bb-products-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .bb-product-carousel-wrap {
            position: relative;
        }

        .bb-slider-wrap {
            position: relative;
        }

        /* No previous/next controls belong to the main hero. */
        .bb-home-section--hero .bb-product-arrow,
        .bb-home-section--hero .owl-nav,
        .bb-home-section--hero .owl-dots {
            display: none !important;
        }

        .bb-product-slider {
            display: flex;
            gap: clamp(1.2rem, 2.5vw, 2rem);
            overflow-x: auto;
            padding: .65rem .35rem 1.45rem;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .bb-product-slider::-webkit-scrollbar {
            display: none;
        }

        .bb-product-slide {
            flex: 0 0 clamp(220px, 21vw, 300px);
            scroll-snap-align: start;
            display: flex;
        }

        .bb-product-arrow {
            position: absolute;
            top: 46%;
            transform: translateY(-50%);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 3px solid #1f1c17;
            background: #5E442B;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .25);
            z-index: 2;
        }

        .bb-product-arrow:hover {
            background: #5E442B;
            color: #fff;
        }

        .bb-product-arrow.prev {
            left: -1.25rem;
        }

        .bb-product-arrow.next {
            right: -1.25rem;
        }

        .bb-why-banner-section {
            background: #fff;
            padding: 0 1rem 4.5rem;
        }

        .bb-why-banner {
            width: 100%;
            position: relative;
            display: grid;
            grid-template-columns: minmax(280px, .9fr) minmax(340px, 1fr);
            align-items: stretch;
            gap: clamp(1.5rem, 4vw, 4rem);
            border: 3px solid #1f1c17;
            border-radius: 34px;
            background: #fffaf0;
            overflow: hidden;
            padding: clamp(1.25rem, 3vw, 3rem) clamp(1.25rem, 3vw, 3rem) 0 0;
            box-shadow: 7px 9px 0 rgba(31, 28, 23, .16);
        }

        .bb-why-banner-image {
            display: block;
            width: 100%;
            max-width: 640px;
            height: auto;
            justify-self: start;
            align-self: end;
            object-fit: contain;
        }

        .bb-why-banner-copy {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            max-width: 660px;
            padding-bottom: clamp(1.25rem, 3vw, 3rem);
        }

        .bb-why-banner-copy .bb-faq-title {
            font-size: clamp(3.2rem, 7vw, 7rem);
        }

        .bb-why-banner-copy p {
            color: #1f1c17;
            max-width: 620px;
            font-size: clamp(1rem, 1.35vw, 1.2rem);
            font-weight: 400;
            margin: 1.15rem 0 1.4rem;
        }

        .bb-why-points {
            display: grid;
            gap: .85rem;
            padding: 0;
            margin: 0 0 1.8rem;
            list-style: none;
        }

        .bb-why-points li {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: #1f1c17;
            font-weight: 400;
        }

        .bb-why-points i {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #5E442B;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-size: .9rem;
            box-shadow: 2px 3px 0 rgba(31, 28, 23, .2);
        }

        .bb-why-banner-copy .bb-category-cta {
            align-self: flex-end;
        }

        @media (max-width: 767.98px) {
            .bb-hero-final {
                padding: 3.5rem 0 2.75rem;
            }

            .bb-hero-final-head {
                margin-bottom: 1rem;
                padding: 0 .45rem;
            }

            .bb-hero-collage {
                display: none;
            }

            .bb-hero-spark {
                display: none;
            }

            .bb-hero-actions {
                margin-top: 1rem;
            }

            .bb-products-head {
                align-items: center;
                text-align: center;
            }

            .bb-product-arrow {
                display: none;
            }

            .bb-why-banner {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin: 0;
                grid-template-columns: 1fr;
                border-radius: 26px;
                gap: 0;
                overflow: hidden;
            }

            .bb-why-banner-copy {
                width: auto;
                min-width: 0;
                padding: 1.25rem 1rem 1.35rem;
            }

            .bb-why-banner-image {
                width: 100%;
                max-width: none;
                max-height: 190px;
                object-fit: cover;
            }

            .bb-why-banner-copy .bb-faq-title {
                font-size: clamp(2.7rem, 14vw, 4rem);
            }

            .bb-why-banner-copy p {
                margin: .85rem 0 1rem;
            }

            .bb-why-points {
                gap: .75rem;
            }

            .bb-why-points li {
                align-items: flex-start;
                gap: .65rem;
                min-width: 0;
            }

            .bb-why-points li span {
                min-width: 0;
                overflow-wrap: anywhere;
                word-break: normal;
            }

            .bb-why-banner-copy .bb-category-cta {
                align-self: flex-end;
            }
        }

        .bb-brown-btn,
        .bb-outline-btn {
            border-radius: 999px;
            padding: .8rem 1.35rem;
            font-weight: 400;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .bb-brown-btn {
            background: #5E442B;
            color: #fff;
            border: 1px solid #5E442B;
        }

        .bb-outline-btn {
            background: #fff;
            color: #5E442B;
            border: 1px solid #5E442B;
        }

        .bb-outline-btn.bb-category-cta {
            border: 3px solid #1f1c17;
            color: #1f1c17;
            background: transparent;
            padding: .45rem 1.15rem;
            min-height: 38px;
            font-size: .85rem;
            border-radius: 999px;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .28);
            transition: none;
        }

        .bb-outline-btn.bb-category-cta:hover {
            color: #1f1c17;
            background: transparent;
            border-color: #1f1c17;
            transform: none;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .28);
        }

        .bb-feature-card,
        .bb-preview-card,
        .bb-search-panel,
        .bb-cta-panel {
            padding: 1.5rem;
            height: 100%;
        }

        .bb-search-panel .form-control,
        .bb-search-panel .form-select {
            border-radius: 14px;
            border-color: rgba(94, 68, 43, .25);
            min-height: 52px;
        }

        .bb-trust-strip {
            background: #fff;
            padding-top: 0;
        }

        .bb-trust-row {
            border: 3px solid #1f1c17;
            border-radius: 28px;
            background: #fff;
            box-shadow: 8px 10px 0 rgba(31, 28, 23, .16);
            padding: 1rem;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
        }

        .bb-trust-item {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-height: 86px;
            border-radius: 20px;
            padding: .85rem;
            background: #fff;
            border: 1px solid rgba(94, 68, 43, .2);
        }

        .bb-trust-item i {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: #5E442B;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex: 0 0 auto;
        }

        .bb-trust-item strong {
            display: block;
            color: #111;
            font-weight: 400;
            line-height: 1.05;
        }

        .bb-trust-item span {
            display: block;
            color: #65776d;
            font-size: .9rem;
            font-weight: 400;
            margin-top: .18rem;
        }

        @media (max-width: 991.98px) {
            .bb-trust-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .bb-trust-row {
                grid-template-columns: 1fr;
            }
        }

        .bb-feedback-section {
            overflow: hidden;
            background: #fff;
        }

        .bb-feedback-head {
            text-align: center;
            margin-bottom: 2.2rem;
        }

        .bb-feedback-title {
            display: inline-block;
            position: relative;
            padding-top: 1.15rem;
            margin: 0;
            color: #111;
            font-family: "Porcelain", cursive;
            font-size: clamp(4rem, 8vw, 8.5rem);
            line-height: .82;
            font-weight: 700;
            letter-spacing: 0;
            text-shadow: .8px 0 #111, 0 .8px #111;
        }

        .bb-feedback-sticker {
            position: absolute;
            top: 0;
            left: 1.4rem;
            transform: rotate(-5deg);
            display: inline-flex;
            min-width: 150px;
            justify-content: center;
            padding: .34rem 1.15rem;
            border: 2px solid #1f1c17;
            border-radius: 8px;
            background: #5E442B;
            color: #fff;
            font-size: clamp(1.28rem, 1.65vw, 1.65rem);
            font-weight: 400;
            line-height: 1;
            box-shadow: 2px 3px 0 rgba(31, 28, 23, .18);
            letter-spacing: .04em;
        }

        .bb-feedback-subtitle {
            margin: .75rem auto 0;
            max-width: 720px;
            color: #5f625c;
            font-weight: 400;
        }

        .bb-feedback-marquee {
            position: relative;
        }

        .bb-feedback-slider {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: .65rem .35rem 1.45rem;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            scrollbar-width: none;
        }

        .bb-feedback-slider::-webkit-scrollbar {
            display: none;
        }

        .bb-feedback-slider .bb-product-slide {
            flex: 0 0 clamp(310px, 32vw, 520px);
            scroll-snap-align: start;
        }

        .bb-feedback-track {
            display: flex;
            gap: 0;
            width: max-content;
            will-change: transform;
            transform: translate3d(0, 0, 0);
            backface-visibility: hidden;
        }

        .bb-feedback-sequence {
            display: flex;
            gap: 1rem;
            flex: 0 0 auto;
            padding-right: 1rem;
        }

        .bb-feedback-track-right {
            justify-self: end;
        }

        .bb-feedback-track-left {
            justify-self: start;
        }

        .bb-feedback-card {
            width: clamp(310px, 32vw, 520px);
            height: 250px;
            min-height: 250px;
            border-radius: 18px;
            border: 2px solid #1f1c17;
            background: #5E442B;
            box-shadow: 7px 9px 0 rgba(31, 28, 23, .18);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #fff;
        }

        .bb-feedback-card::after {
            content: "";
            display: block;
            height: 3px;
            width: 100%;
            background: #fff;
            border-radius: 999px;
            margin-top: 1rem;
            opacity: .75;
        }

        .bb-feedback-quote {
            margin: 0;
            font-size: clamp(1rem, 1.25vw, 1.28rem);
            line-height: 1.35;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 4;
            overflow: hidden;
            font-weight: 400;
            font-style: italic;
        }

        .bb-feedback-person {
            display: flex;
            align-items: center;
            gap: .85rem;
            margin-top: 1.25rem;
        }

        .bb-feedback-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .75);
            background: #fff;
            color: #5E442B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 400;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .18);
            flex: 0 0 auto;
            overflow: hidden;
        }

        .bb-feedback-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .bb-feedback-person strong {
            display: block;
            color: #fff;
            font-weight: 400;
            font-size: 1.15rem;
        }

        .bb-feedback-person span {
            display: block;
            color: rgba(255, 255, 255, .78);
            font-weight: 400;
        }

        .bb-feedback-empty {
            max-width: 720px;
            margin: 0 auto;
            border: 3px solid #1f1c17;
            border-radius: 24px;
            background: #fff;
            padding: 2rem;
            text-align: center;
            font-weight: 400;
            box-shadow: 7px 8px 0 rgba(31, 28, 23, .16);
        }

        .bb-faq-showcase {
            background: #fff;
            color: #171512;
        }

        .bb-faq-layout {
            display: grid;
            grid-template-columns: minmax(220px, 360px) minmax(0, 1fr);
            gap: clamp(2rem, 7vw, 6rem);
            align-items: start;
        }

        .bb-faq-heading {
            margin-bottom: clamp(1.75rem, 4vw, 3rem);
        }

        .bb-faq-layout--stacked {
            display: block;
            max-width: 900px;
            margin: 0 auto;
        }

        .bb-faq-title {
            font-family: "Porcelain", cursive;
            color: #111;
            font-size: clamp(3.8rem, 8vw, 7.5rem);
            line-height: .78;
            margin: 0;
            position: relative;
            display: inline-block;
            padding-top: 1.15rem;
        }

        .bb-faq-sticker {
            position: absolute;
            left: 1.15rem;
            top: 0;
            display: inline-flex;
            transform: rotate(-4deg);
            padding: .18rem .45rem;
            border: 2px solid #1f1c17;
            border-radius: 8px;
            background: #5E442B;
            color: #fff;
            font-size: clamp(.72rem, 1.1vw, .95rem);
            font-weight: 400;
            font-family: "Heebo", sans-serif;
            line-height: 1;
            box-shadow: 2px 3px 0 rgba(31, 28, 23, .18);
        }

        .bb-category-sticker {
            position: absolute;
            left: 1.6rem;
            top: 0;
            display: inline-flex;
            transform: rotate(-4deg);
            padding: .2rem .75rem;
            border: 2px solid #1f1c17;
            border-radius: 8px;
            background: #5E442B;
            color: #fff;
            font-size: clamp(.78rem, 1.1vw, 1rem);
            font-weight: 400;
            font-family: "Heebo", sans-serif;
            line-height: 1;
            box-shadow: 2px 3px 0 rgba(31, 28, 23, .18);
        }

        .bb-faq-contact {
            margin-top: clamp(5rem, 18vw, 13rem);
        }

        .bb-faq-contact--bottom {
            margin: clamp(2rem, 5vw, 3.5rem) auto 0;
            text-align: center;
        }

        .bb-faq-contact h3 {
            color: #111;
            font-family: "Heebo", sans-serif;
            font-size: clamp(1.55rem, 2.5vw, 2.3rem);
            font-weight: 400;
            line-height: .9;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }

        .bb-faq-contact .bb-outline-btn {
            border: 3px solid #1f1c17;
            color: #1f1c17;
            background: transparent;
            padding: .45rem 1.15rem;
            min-height: 38px;
            font-size: .85rem;
            border-radius: 999px;
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .28);
            transition: .2s ease;
        }

        .bb-faq-contact .bb-outline-btn:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 5px 0 rgba(31, 28, 23, .32);
        }

        .bb-faq-list {
            display: grid;
            gap: .85rem;
        }

        .bb-faq-item {
            border: 2px solid rgba(31, 28, 23, .72);
            border-radius: 16px;
            background: rgba(255, 255, 255, .56);
            box-shadow: 3px 4px 0 rgba(31, 28, 23, .12);
            overflow: hidden;
        }

        .bb-faq-item summary {
            list-style: none;
            cursor: pointer;
            min-height: 58px;
            padding: 1rem 3.5rem 1rem 1.15rem;
            position: relative;
            color: #111;
            font-weight: 400;
            letter-spacing: 0;
        }

        .bb-faq-item summary::-webkit-details-marker {
            display: none;
        }

        .bb-faq-item summary::after {
            content: "+";
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #5E442B;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 400;
            line-height: 1;
        }

        .bb-faq-item[open] summary::after {
            content: "-";
            background: #fff;
            color: #5E442B;
        }

        .bb-faq-item p {
            padding: 0 1.15rem 1rem;
            margin: 0;
            color: #3b3832;
            font-weight: 400;
            line-height: 1.45;
        }

        .bb-signup-cta {
            background: #f7f7f5;
            position: relative;
            overflow: hidden;
        }

        .bb-signup-panel {
            min-height: 360px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            padding: clamp(2rem, 5vw, 4rem) 1rem;
        }

        .bb-signup-title {
            max-width: 660px;
            color: #111;
            font-family: "Porcelain", cursive;
            font-weight: 700;
            font-size: clamp(2.25rem, 5vw, 4.8rem);
            line-height: .94;
            letter-spacing: 0;
            margin: 0 0 2rem;
        }

        .bb-signup-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: .9rem;
            flex-wrap: wrap;
        }

        .bb-signup-btn {
            min-width: 150px;
            min-height: 48px;
            border-radius: 999px;
            border: 3px solid #1f1c17;
            box-shadow: 3px 5px 0 rgba(31, 28, 23, .22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 400;
            color: #1f1c17;
            transition: .2s ease;
        }

        .bb-signup-btn:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 6px 0 rgba(31, 28, 23, .26);
        }

        .bb-signup-btn-primary {
            background: #5E442B;
            color: #fff;
        }

        .bb-signup-btn-outline {
            background: #fff;
            color: #5E442B;
        }

        .bb-cta-sticker {
            position: absolute;
            width: clamp(230px, 24vw, 380px);
            aspect-ratio: 2.5 / 1;
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            filter: drop-shadow(4px 6px 0 rgba(31, 28, 23, .16));
            text-indent: -9999px;
            overflow: hidden;
        }

        .bb-cta-sticker-left {
            left: 4%;
            top: 38%;
            background-image: url("{{ asset('assets/frontend/img/Home Page/Stickers_and_Icons/Sticker 1 (1).png') }}");
            transform: rotate(-10deg);
        }

        .bb-cta-sticker-right {
            right: 4%;
            top: 16%;
            background-image: url("{{ asset('assets/frontend/img/Home Page/Stickers_and_Icons/Sticker 1 (2).png') }}");
            transform: rotate(10deg);
        }

        .bb-cta-spark {
            position: absolute;
            width: 30px;
            height: 30px;
            transform: rotate(45deg);
            background: #f6df52;
            clip-path: polygon(50% 0, 62% 38%, 100% 50%, 62% 62%, 50% 100%, 38% 62%, 0 50%, 38% 38%);
            filter: drop-shadow(2px 3px 0 rgba(31, 28, 23, .2));
        }

        .bb-cta-spark-one {
            left: 10%;
            top: 20%;
        }

        .bb-cta-spark-two {
            right: 8%;
            top: 52%;
        }

        @media (max-width: 767.98px) {
            .bb-faq-layout {
                grid-template-columns: 1fr;
            }

            .bb-faq-side {
                order: 2;
            }

            .bb-faq-list {
                order: 1;
            }

            .bb-faq-contact {
                margin-top: 1.5rem;
            }

            .bb-feedback-marquee {
                display: block;
                width: 100%;
                margin-left: 0;
                overflow-x: auto;
                overflow-y: hidden;
                padding: .25rem .1rem 1.1rem;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
            }

            .bb-feedback-track {
                transform: none !important;
            }

            .bb-feedback-track-right {
                display: none;
            }

            .bb-feedback-sequence[aria-hidden="true"] {
                display: none;
            }

            .bb-feedback-sequence {
                gap: .9rem;
                padding-right: 0;
            }

            .bb-feedback-card {
                flex: 0 0 min(82vw, 330px);
                min-height: 230px;
                scroll-snap-align: center;
            }

            .bb-signup-panel {
                padding: 3rem 1rem 2.6rem;
                overflow: hidden;
            }

            .bb-cta-sticker {
                display: block;
                width: clamp(92px, 34vw, 132px);
            }

            .bb-cta-sticker-left {
                left: -1rem;
                top: .75rem;
            }

            .bb-cta-sticker-right {
                right: -1rem;
                top: auto;
                bottom: .55rem;
            }

            .bb-cta-spark {
                display: block;
                width: 16px;
                height: 16px;
            }

            .bb-cta-spark-one {
                left: 1rem;
                top: 44%;
            }

            .bb-cta-spark-two {
                right: 1.1rem;
                top: 28%;
            }
        }

        html,
        body,
        .bb-page-shell,
        .bb-section,
        .bb-section-soft,
        .bb-trust-strip,
        .bb-feedback-section,
        .bb-faq-showcase,
        .bb-signup-cta,
        .bb-hero {
            background-color: #fff !important;
            background-image:
                linear-gradient(rgba(94, 68, 43, .08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(94, 68, 43, .08) 1px, transparent 1px) !important;
            background-size: 34px 34px !important;
        }

        #spinner {
            background: #fff !important;
            pointer-events: none;
        }

        .bb-community-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(21, 17, 13, .58);
        }

        .bb-community-modal.is-open {
            display: flex;
        }

        .bb-community-card {
            width: min(520px, calc(100vw - 2rem));
            min-height: 0;
            max-height: calc(100vh - 1.5rem);
            display: block;
            background: #5E442B;
            color: #fff;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .35);
            position: relative;
            overflow: hidden;
            border: 3px solid #1f1c17;
            border-radius: 18px;
        }

        .bb-community-close {
            position: absolute;
            top: .85rem;
            right: .85rem;
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            color: #fff;
            font-size: 1.6rem;
            line-height: 1;
            z-index: 2;
        }

        .bb-community-copy {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem 1.8rem 1.6rem;
        }

        .bb-community-logo {
            font-family: "Porcelain", cursive;
            font-size: clamp(2.1rem, 5vw, 3.1rem);
            line-height: .9;
            margin-bottom: .75rem;
            color: #fff;
        }

        .bb-community-copy h2 {
            color: #fff;
            font-family: inherit;
            font-size: clamp(1.55rem, 3.6vw, 2.1rem);
            line-height: 1.08;
            font-weight: 400;
            letter-spacing: 0;
            margin-bottom: .75rem;
            text-transform: none;
        }

        .bb-community-copy p {
            max-width: 420px;
            color: rgba(253, 246, 236, .86);
            font-weight: 400;
            margin-bottom: 1rem;
        }

        .bb-community-form {
            width: min(420px, 100%);
            display: grid;
            gap: .75rem;
        }

        .bb-community-form input {
            width: 100%;
            min-height: 42px;
            border: 1px solid rgba(253, 246, 236, .65);
            background: transparent;
            color: #fff;
            padding: .65rem 1rem;
            font-weight: 400;
        }

        .bb-community-form input::placeholder {
            color: rgba(253, 246, 236, .78);
        }

        .bb-community-actions {
            display: grid;
            gap: .65rem;
            margin-top: .9rem;
        }

        .bb-community-cta {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #1f1c17;
            border-radius: 999px;
            box-shadow: 3px 5px 0 rgba(31, 28, 23, .22);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: .02em;
            text-decoration: none;
            transition: .2s ease;
        }

        .bb-community-cta-primary {
            background: #fff;
            color: #1f1c17;
        }

        .bb-community-cta-secondary {
            background: transparent;
            color: #fff;
        }

        .bb-community-cta:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 6px 0 rgba(31, 28, 23, .26);
        }

        .bb-community-cta-primary:hover {
            color: #1f1c17;
            background: #FFFFFF;
        }

        .bb-community-cta-secondary:hover {
            color: #fff;
            background: rgba(253, 246, 236, .08);
        }

        .bb-community-legal {
            color: rgba(253, 246, 236, .76);
            font-size: .78rem;
            line-height: 1.45;
            margin-top: .75rem;
        }

        .bb-community-image {
            display: none;
        }

        body.bb-community-locked {
            overflow: hidden;
        }

        .header-carousel::before,
        .page-header::before {
            display: none !important;
        }

        .header-carousel .owl-nav,
        .header-carousel .owl-dots {
            display: none !important;
        }

        @media (max-width: 991.98px) {
            .bb-community-card {
                width: min(480px, calc(100vw - 1rem));
            }

            .bb-community-image {
                display: none;
            }
        }
    </style>
</head>


<body>
    <div class="container-fluid p-0 bb-page-shell">
        <!-- Spinner Start -->
        <div id="spinner" class="show position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        @include('partials.navbar')

        <main class="@yield('main_class', 'store-main')">
            @yield('content')
        </main>

        @include('partials.footer')

        <!-- Floating store actions -->
        <div class="bb-floating-store-actions" aria-label="Quick actions">
            <a href="{{ route('cart.index') }}" class="bb-floating-cart" aria-label="Open cart">
                <i class="fa fa-shopping-bag"></i>
                <span data-cart-count>{{ $cartCount }}</span>
            </a>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script>
        (() => {
            const hideSpinner = () => document.getElementById("spinner")?.classList.remove("show");
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", hideSpinner, { once: true });
            } else {
                hideSpinner();
            }
            window.setTimeout(hideSpinner, 400);
        })();
    </script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/frontend/lib/wow/wow.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('assets/frontend/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/sliders.js') }}?v={{ filemtime(public_path('assets/js/sliders.js')) }}"></script>
    <script>
        (() => {
            const menu = document.getElementById("bbFullMenu");
            const openButtons = document.querySelectorAll(".bb-menu-open");
            const closeButton = document.querySelector(".bb-menu-close");
            const searchInput = menu?.querySelector(".bb-full-menu-search input");
            if (!menu || !openButtons.length || !closeButton) return;

            const openMenu = () => {
                menu.classList.add("is-open");
                menu.setAttribute("aria-hidden", "false");
                openButtons.forEach((button) => button.setAttribute("aria-expanded", "true"));
                document.body.classList.add("bb-menu-locked");
                if (window.matchMedia("(max-width: 991.98px)").matches && searchInput) {
                    window.setTimeout(() => searchInput.focus(), 0);
                } else {
                    closeButton.focus();
                }
            };

            const closeMenu = () => {
                menu.classList.remove("is-open");
                menu.setAttribute("aria-hidden", "true");
                openButtons.forEach((button) => button.setAttribute("aria-expanded", "false"));
                document.body.classList.remove("bb-menu-locked");
                openButtons[0].focus();
            };

            openButtons.forEach((button) => button.addEventListener("click", openMenu));
            closeButton.addEventListener("click", closeMenu);
            menu.querySelectorAll("a").forEach((link) => {
                link.addEventListener("click", closeMenu);
            });
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && menu.classList.contains("is-open")) {
                    closeMenu();
                }
            });

            document.querySelectorAll(".bb-category-with-submenu, .bb-full-menu-category").forEach((item) => {
                const button = item.querySelector(".bb-category-accordion-toggle, .bb-full-menu-category-toggle");
                const panel = item.querySelector(".bb-category-submenu, .bb-full-menu-sublist");
                if (!button || !panel) return;

                button.addEventListener("click", (event) => {
                    event.preventDefault();
                    const isOpen = item.classList.toggle("is-open");
                    button.setAttribute("aria-expanded", String(isOpen));
                    panel.hidden = !isOpen;
                });
            });
        })();
    </script>
    <script>
        (() => {
            const updateCartCount = (count) => {
                document.querySelectorAll("[data-cart-count]").forEach((target) => {
                    target.textContent = count;
                    target.animate?.([
                        { transform: "scale(1)", opacity: 1 },
                        { transform: "scale(1.18)", opacity: .82 },
                        { transform: "scale(1)", opacity: 1 }
                    ], { duration: 240, easing: "ease-out" });
                });
            };

            document.addEventListener("submit", async (event) => {
                const form = event.target;
                const isCartForm = form instanceof HTMLFormElement && form.action.includes("/cart/");
                const isCartPageUpdate = isCartForm && form.action.includes("/update") && Boolean(form.closest(".bb-cart-page-shell"));

                if (
                    !isCartForm ||
                    (form.action.includes("/update") && !isCartPageUpdate)
                ) {
                    return;
                }

                event.preventDefault();
                const submitter = event.submitter;
                submitter?.setAttribute("disabled", "disabled");
                form.classList.add("is-submitting");

                try {
                    const response = await fetch(form.action, {
                        method: form.method || "POST",
                        body: new FormData(form),
                        headers: {
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        credentials: "same-origin"
                    });

                    if (!response.ok) {
                        form.submit();
                        return;
                    }

                    const data = await response.json();
                    updateCartCount(data.cart_count ?? 0);

                    if (isCartPageUpdate) {
                        const cartPageResponse = await fetch("{{ route('cart.index') }}", {
                            headers: { "X-Requested-With": "XMLHttpRequest" },
                            credentials: "same-origin"
                        });
                        const html = await cartPageResponse.text();
                        const parsed = new DOMParser().parseFromString(html, "text/html");
                        const nextCart = parsed.querySelector(".bb-cart-page-shell");
                        const currentCart = document.querySelector(".bb-cart-page-shell");

                        if (nextCart && currentCart) {
                            currentCart.replaceWith(nextCart);
                        }
                    }
                } catch (error) {
                    form.submit();
                } finally {
                    submitter?.removeAttribute("disabled");
                    form.classList.remove("is-submitting");
                }
            });
        })();
    </script>
    <script>
        (() => {
            const modal = document.getElementById("bbCommunityModal");
            if (!modal) return;

            const closeButton = modal.querySelector(".bb-community-close");
            const newsletterButton = modal.querySelector(".bb-newsletter-btn");
            const title = modal.querySelector("#bbCommunityTitle");

            const openModal = () => {
                modal.classList.add("is-open");
                modal.setAttribute("aria-hidden", "false");
                document.body.classList.add("bb-community-locked");
                closeButton?.focus();
            };

            const closeModal = () => {
                modal.classList.remove("is-open");
                modal.setAttribute("aria-hidden", "true");
                document.body.classList.remove("bb-community-locked");
            };

            closeButton?.addEventListener("click", closeModal);
            modal.addEventListener("click", (event) => {
                if (event.target === modal) closeModal();
            });
            newsletterButton?.addEventListener("click", () => {
                if (title) title.textContent = "You're in!";
                window.setTimeout(closeModal, 850);
            });
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && modal.classList.contains("is-open")) {
                    closeModal();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>

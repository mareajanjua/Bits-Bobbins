@extends('layouts.app')

@section('title', 'Bits&Bobbins')
@section('main_class', '')

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
    $bestSellerProducts = DB::table('product')
        ->join('category', 'product.category_code', '=', 'category.category_code')
        ->leftJoin('subcategory', 'product.subcategory_id', '=', 'subcategory.subcategory_id')
        ->leftJoin('stock', 'product.product_id', '=', 'stock.product_id')
        ->leftJoin('feedback', 'product.product_id', '=', 'feedback.product_id')
        ->leftJoin('order_item', 'product.product_id', '=', 'order_item.product_id')
        ->where('product.is_active', 1)
        ->select('product.*', 'category.category_name', 'subcategory.subcategory_name', DB::raw('COALESCE(stock.quantity_available, 0) as stock_qty'), DB::raw('COALESCE(AVG(feedback.rating), 0) as avg_rating'), DB::raw('COUNT(feedback.feedback_id) as review_count'), DB::raw('COALESCE(SUM(order_item.quantity), 0) as sold_qty'))
        ->groupBy('product.product_id', 'product.category_code', 'product.subcategory_id', 'product.product_number', 'product.product_name', 'product.description', 'product.price', 'product.image_front', 'product.image_hover', 'product.has_warranty', 'product.warranty_months', 'product.is_active', 'product.created_at', 'product.updated_at', 'category.category_name', 'subcategory.subcategory_name', 'stock.quantity_available')
        ->orderByDesc('avg_rating')
        ->orderByDesc('review_count')
        ->orderByDesc('sold_qty')
        ->limit(4)
        ->get();
    $feedbackPreview = DB::table('feedback')
        ->join('customer', 'feedback.customer_id', '=', 'customer.customer_id')
        ->leftJoin('product', 'feedback.product_id', '=', 'product.product_id')
        ->select('customer.full_name', 'customer.profile_photo', 'feedback.rating', 'feedback.message', 'feedback.submitted_at', 'product.product_name')
        ->orderByDesc('feedback.submitted_at')
        ->limit(8)
        ->get();
    $feedbackSlides = $feedbackPreview->values();
    $feedbackTopRow = $feedbackSlides->filter(fn ($item, $index) => $index % 2 === 0)->values();
    $feedbackBottomRow = $feedbackSlides->filter(fn ($item, $index) => $index % 2 === 1)->values();
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

@section('content')
        <?php if (! ($customer || $admin || $employee)): ?>
            <div class="bb-community-modal" id="bbCommunityModal" aria-hidden="true">
                <div class="bb-community-card" role="dialog" aria-modal="true" aria-labelledby="bbCommunityTitle">
                    <button type="button" class="bb-community-close" aria-label="Close community popup">&times;</button>
                    <div class="bb-community-copy">
                        <div class="bb-community-logo">Bits&Bobbins</div>
                        <h2 id="bbCommunityTitle">Join Bobbins Community</h2>
                        <p>Be the first to know about playful new arrivals, sweet kids' picks, special offers, and Bits&Bobbins updates.</p>
                        <form class="bb-community-form" action="<?php echo e(route('customer.register')); ?>" method="GET">
                            <input type="email" name="email" placeholder="Email">
                            <input type="tel" name="phone" placeholder="Phone Number">
                            <div class="bb-community-actions">
                                <a href="<?php echo e(route('customer.register')); ?>" class="bb-community-cta bb-community-cta-primary">Become Bobbins Customer</a>
                                <button type="button" class="bb-community-cta bb-community-cta-secondary bb-newsletter-btn">Subscribe to Newsletter</button>
                            </div>
                        </form>
                        <div class="bb-community-legal">By joining, you agree to receive cheerful Bits&Bobbins news and offers. You can unsubscribe anytime.</div>
                    </div>
                    <div class="bb-community-image" aria-hidden="true"></div>
                </div>
            </div>
        <?php endif; ?>


        <section class="bb-home-section bb-home-section--hero bb-hero-final">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="bb-hero-final-head">
                            <h1 class="bb-hero-final-title">Always Find The <span class="bb-hero-title-word"><span class="bb-category-sticker">Tiny picks</span>Sweetest</span> Little Treasures</h1>
                            <p class="bb-hero-final-copy">Dolls, gifts, stationery, bags, wallets, and playful accessories for happy little everyday moments.</p>
                        </div>
                        <div class="bb-hero-collage" aria-label="Bits&Bobbins product collage">
                            <?php $__currentLoopData = $heroImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $heroImage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="bb-hero-photo bb-hero-photo-<?php echo e($loop->index); ?>">
                                    <img src="<?php echo e(asset($heroImage)); ?>?v=hero-clean-2" alt="Bits&Bobbins hero image <?php echo e($loop->iteration); ?>">
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <img class="bb-hero-face" src="<?php echo e(asset('assets/frontend/img/Home Page/Stickers_and_Icons/Smile.png')); ?>" alt="Smiling sticker">
                        </div>
                        <div class="bb-hero-actions">
                            <a href="<?php echo e(route('store.products')); ?>" class="bb-outline-btn bb-category-cta">Shop Now <i class="fa fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--categories bb-section bb-section-soft" id="categories">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="bb-section-head text-center">
                            <h2 class="bb-faq-title"><span class="bb-category-sticker">Categories</span>Find Your Perfect Little World</h2>
                            <span class="bb-section-eyebrow">Discover the most loved Bits&Bobbins categories for tiny treasures, sweet gifts, and playful everyday picks.</span>
                        </div>
                        <div class="bb-slider-wrap bb-slider-wrap--categories bb-category-carousel-wrap">
                            <button type="button" class="bb-product-arrow prev" data-slider-control="prev" aria-label="Previous categories"><i class="fa fa-arrow-left"></i></button>
                            <div class="bb-slider bb-slider--categories bb-category-slider">
                            <?php if ($frontendCategories->isNotEmpty()): ?>
                            <?php foreach ($frontendCategories as $category): ?>
                                <?php
                                    $categoryKey = strtolower(trim($category->category_name));
                                    $categoryImage = $categoryImages[$categoryKey] ?? null;
                                ?>
                                <div class="bb-category-slide">
                                    <a href="<?php echo e(route('store.products', ['category' => $category->category_code])); ?>" class="bb-category-card">
                                        <div class="bb-category-image">
                                            <?php if ($categoryImage): ?>
                                                <img src="<?php echo e(asset($categoryImage)); ?>" alt="<?php echo e($category->category_name); ?>">
                                            <?php else: ?>
                                                <i class="fa fa-gift"></i>
                                            <?php endif; ?>
                                        </div>
                                        <h3><?php echo e($category->category_name); ?></h3>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <div class="bb-category-slide">
                                    <div class="bb-preview-card text-center">No categories available yet.</div>
                                </div>
                            <?php endif; ?>
                            </div>
                            <button type="button" class="bb-product-arrow next" data-slider-control="next" aria-label="Next categories"><i class="fa fa-arrow-right"></i></button>
                        </div>
                        <div class="bb-category-cta-row">
                            <a href="<?php echo e(route('store.products')); ?>" class="bb-outline-btn bb-category-cta">Explore All <i class="fa fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--new-products bb-section">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="bb-section-head bb-products-head flex-column flex-md-row">
                            <div>
                                <h2 class="bb-faq-title"><span class="bb-category-sticker">New Picks</span>Tiny Treasures</h2>
                                <p class="bb-section-eyebrow text-md-start">Fresh playful finds from the real product shelf.</p>
                            </div>
                            <a href="<?php echo e(route('store.products', ['sort' => 'newest'])); ?>" class="bb-outline-btn bb-category-cta">View More <i class="fa fa-arrow-right ms-2"></i></a>
                        </div>
                        <div class="bb-slider-wrap bb-slider-wrap--products bb-product-carousel-wrap">
                            <button type="button" class="bb-product-arrow prev" data-slider-control="prev" aria-label="Previous products"><i class="fa fa-arrow-left"></i></button>
                            <div class="bb-slider bb-slider--products bb-product-slider">
                            <?php if ($latestProducts->isNotEmpty()): ?>
                            <?php foreach ($latestProducts as $product): ?>
                                <div class="bb-product-slide">
                                    @include('store.partials.product-card', ['product' => $product, 'variant' => 'home'])
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <div class="bb-product-slide">
                                    <div class="bb-preview-card text-center">No products available yet.</div>
                                </div>
                            <?php endif; ?>
                            </div>
                            <button type="button" class="bb-product-arrow next" data-slider-control="next" aria-label="Next products"><i class="fa fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--why bb-why-banner-section" id="about">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="bb-why-banner">
                            <img class="bb-why-banner-image" src="<?php echo e(asset('assets/frontend/img/Home Page/Category/banner.png')); ?>" alt="Bits&Bobbins playful shop banner">
                            <div class="bb-why-banner-copy">
                                <h2 class="bb-faq-title"><span class="bb-category-sticker">Why us?</span>Tiny Joy, Delivered Right</h2>
                                <p>Bits&Bobbins keeps kids' shopping cheerful and practical, with playful picks, clear product details, and simple ordering for busy families.</p>
                                <ul class="bb-why-points">
                                    <li><i class="bi bi-check2"></i><span>Fresh dolls, gifts, stationery, bags, wallets, and accessories.</span></li>
                                    <li><i class="bi bi-check2"></i><span>Doorstep delivery with secure payment options.</span></li>
                                    <li><i class="bi bi-check2"></i><span>Easy 7-day return or replace support where eligible.</span></li>
                                    <li><i class="bi bi-check2"></i><span>Warranty details shown clearly when applicable.</span></li>
                                </ul>
                                <a href="<?php echo e(route('store.products')); ?>" class="bb-outline-btn bb-category-cta">Shop Now <i class="fa fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--best-sellers bb-section bb-section-soft">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="bb-section-head bb-products-head flex-column flex-md-row">
                            <div>
                                <h2 class="bb-faq-title"><span class="bb-category-sticker">Popular</span>Best Sellers</h2>
                                <p class="bb-section-eyebrow text-md-start">Highest-rated favourites from real customer product reviews.</p>
                            </div>
                            <a href="<?php echo e(route('store.products')); ?>" class="bb-outline-btn bb-category-cta">View More <i class="fa fa-arrow-right ms-2"></i></a>
                        </div>
                        <div class="bb-slider-wrap bb-slider-wrap--products bb-product-carousel-wrap">
                            <button type="button" class="bb-product-arrow prev" data-slider-control="prev" aria-label="Previous best sellers"><i class="fa fa-arrow-left"></i></button>
                            <div class="bb-slider bb-slider--products bb-product-slider">
                            <?php if ($bestSellerProducts->isNotEmpty()): ?>
                            <?php foreach ($bestSellerProducts as $product): ?>
                                <div class="bb-product-slide">
                                    @include('store.partials.product-card', ['product' => $product, 'variant' => 'home', 'showRating' => true])
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <div class="bb-product-slide">
                                    <div class="bb-preview-card text-center">No rated products yet.</div>
                                </div>
                            <?php endif; ?>
                            </div>
                            <button type="button" class="bb-product-arrow next" data-slider-control="next" aria-label="Next best sellers"><i class="fa fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--feedback bb-section bb-feedback-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="bb-section-head bb-feedback-head">
                            <h2 class="bb-feedback-title"><span class="bb-feedback-sticker">Feedback</span>What people are saying?</h2>
                            <p class="bb-feedback-subtitle">Real words from Bits&Bobbins customers, pulled from submitted feedback.</p>
                        </div>
                        <?php if($feedbackSlides->isNotEmpty()): ?>
                            <div class="bb-slider-wrap bb-slider-wrap--feedback bb-feedback-carousel-wrap" aria-label="Customer feedback slider">
                                <button type="button" class="bb-product-arrow prev" data-slider-control="prev" aria-label="Previous feedback"><i class="fa fa-arrow-left"></i></button>
                                <div class="bb-slider bb-feedback-slider">
                                    @foreach ($feedbackSlides as $item)
                                        <div class="bb-product-slide">
                                            <article class="bb-feedback-card">
                                                <p class="bb-feedback-quote">&ldquo;{{ \Illuminate\Support\Str::limit($item->message, 150) }}&rdquo;</p>
                                                <div class="bb-feedback-person">
                                                    <span class="bb-feedback-avatar">
                                                        @if (! empty($item->profile_photo))
                                                            <img src="{{ asset($item->profile_photo) }}" alt="{{ $item->full_name }}">
                                                        @else
                                                            {{ strtoupper(\Illuminate\Support\Str::substr($item->full_name, 0, 1)) }}
                                                        @endif
                                                    </span>
                                                    <div>
                                                        <strong>{{ $item->full_name }}</strong>
                                                        <span>{{ $item->rating ?? 'N/A' }}/5 rating{{ $item->product_name ? ' - ' . $item->product_name : '' }}</span>
                                                    </div>
                                                </div>
                                            </article>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="bb-product-arrow next" data-slider-control="next" aria-label="Next feedback"><i class="fa fa-arrow-right"></i></button>
                            </div>
                        <?php else: ?>
                            <div class="bb-feedback-empty">No feedback available yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--faq bb-section bb-faq-showcase">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="bb-faq-heading">
                            <h2 class="bb-faq-title"><span class="bb-faq-sticker">FAQ</span>Got Questions?</h2>
                        </div>
                        <div class="bb-faq-layout bb-faq-layout--stacked">
                            <div class="bb-faq-list">
                                <?php $__empty_1 = true; $__currentLoopData = $faqPreview; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <details class="bb-faq-item" <?php if($loop->first): ?> open <?php endif; ?>>
                                        <summary><?php echo e($faq->question); ?></summary>
                                        <p><?php echo e($faq->answer); ?></p>
                                    </details>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="bb-faq-item">
                                        <p class="p-3">No FAQs available yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bb-faq-contact bb-faq-contact--bottom">
                            <h3>Still got<br>questions?</h3>
                            <a href="{{ route('store.about') }}#about-contact" class="bb-outline-btn">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bb-home-section bb-home-section--signup bb-section bb-signup-cta">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="bb-signup-panel">
                            <span class="bb-cta-spark bb-cta-spark-one" aria-hidden="true"></span>
                            <span class="bb-cta-spark bb-cta-spark-two" aria-hidden="true"></span>
                            <span class="bb-cta-sticker bb-cta-sticker-left">New picks</span>
                            <span class="bb-cta-sticker bb-cta-sticker-right">Shop happy</span>
                            <h2 class="bb-signup-title">Be the first to catch tiny treasures, sweet deals, and playful new arrivals.</h2>
                            <div class="bb-signup-actions">
                                <a href="<?php echo e(route('customer.register')); ?>" class="bb-signup-btn bb-signup-btn-primary">Make Account</a>
                                <a href="<?php echo e(route('store.products')); ?>" class="bb-outline-btn bb-category-cta">Shop All</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        
        <!-- Team End -->

@endsection

@extends('layouts.app')

@section('title', 'About')

@section('content')
<div class="bb-about-page">
  <section class="bb-page-section bb-about-section bb-about-brand-section" id="about-brand">
    <div class="container">
      <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6">
          <div class="bb-about-brand-image">
            <img src="{{ asset('assets/frontend/img/Home Page/login/about image.jfif') }}" alt="Bits&Bobbins about image">
          </div>
        </div>
        <div class="col-lg-6">
          <div class="bb-about-brand-copy">
            <h1>What is Bits&amp;Bobbins?</h1>
            <p>Bits&amp;Bobbins is a playful ecommerce space for tiny treasures, soft everyday gifts, and thoughtful product finds. We bring together dolls, stationery, bags, wallets, cute accessories, and little everyday surprises in one warm, easy-to-browse shop. The idea is simple: customers should be able to discover something sweet, understand the product clearly, check out without stress, and come back later to track orders, manage details, and share feedback that helps other shoppers choose with confidence.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="bb-page-section bb-about-section bb-about-feedback-section" id="about-feedback">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <div class="bb-section-head bb-about-section-head">
            <h2>Customer words</h2>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="bb-slider-wrap bb-product-carousel-wrap bb-about-feedback-carousel">
            <button type="button" class="bb-product-nav bb-product-nav-prev" data-product-slide="prev" aria-label="Previous customer words">
              <i class="fa fa-arrow-left"></i>
            </button>

            <div class="bb-slider bb-product-slider">
              @forelse ($feedback as $item)
                <div class="bb-product-slide">
                  <article class="bb-about-feedback-card">
                    <p>&ldquo;{{ \Illuminate\Support\Str::limit($item->message, 150) }}&rdquo;</p>
                    <div>
                      <span class="bb-about-avatar">
                        @if (! empty($item->profile_photo))
                          <img src="{{ asset($item->profile_photo) }}" alt="{{ $item->full_name }}">
                        @else
                          {{ strtoupper(\Illuminate\Support\Str::substr($item->full_name, 0, 1)) }}
                        @endif
                      </span>
                      <span>
                        <strong>{{ $item->full_name }}</strong>
                        <small>{{ $item->rating }}/5 rating{{ $item->product_name ? ' - ' . $item->product_name : '' }}</small>
                      </span>
                    </div>
                  </article>
                </div>
              @empty
                <div class="bb-product-slide">
                  <article class="bb-about-feedback-card">
                    <p>No customer feedback has been submitted yet.</p>
                    <div>
                      <span class="bb-about-avatar"><i class="bi bi-chat-square-heart"></i></span>
                      <span><strong>Bits&amp;Bobbins</strong><small>Feedback will appear here after purchases.</small></span>
                    </div>
                  </article>
                </div>
              @endforelse
            </div>

            <button type="button" class="bb-product-nav bb-product-nav-next" data-product-slide="next" aria-label="Next customer words">
              <i class="fa fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="bb-page-section bb-about-section bb-about-faq-section" id="about-faq">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <div class="bb-faq-layout">
            <div class="bb-faq-side">
              <h2 class="bb-faq-title">Got Questions?<span class="bb-faq-sticker">FAQ</span></h2>
              <div class="bb-faq-contact">
                <h3>Still got<br>questions?</h3>
                <a href="#about-contact" class="bb-outline-btn">Contact Us</a>
              </div>
            </div>
            <div class="bb-faq-list">
              @forelse ($faqs as $faq)
                <details class="bb-faq-item" @if ($loop->first) open @endif>
                  <summary>{{ $faq->question }}</summary>
                  <p>{{ $faq->answer }}</p>
                </details>
              @empty
                <div class="bb-faq-item">
                  <p class="p-3">No FAQs available yet.</p>
                </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="bb-page-section bb-about-section bb-about-contact-section" id="about-contact">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <div class="bb-section-head bb-about-section-head">
            <h2>Contact</h2>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="bb-about-contact-grid">
            <a href="mailto:support@bitsandbobbins.test" target="_blank" rel="noopener">
              <i class="bi bi-envelope"></i>
              <span>Customer Support</span>
              <strong>support@bitsandbobbins.test</strong>
            </a>
            <a href="mailto:orders@bitsandbobbins.test" target="_blank" rel="noopener">
              <i class="bi bi-bag-check"></i>
              <span>Orders</span>
              <strong>orders@bitsandbobbins.test</strong>
            </a>
            <a href="mailto:feedback@bitsandbobbins.test" target="_blank" rel="noopener">
              <i class="bi bi-chat-square-heart"></i>
              <span>Feedback</span>
              <strong>feedback@bitsandbobbins.test</strong>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

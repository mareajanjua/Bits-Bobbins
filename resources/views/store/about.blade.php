@extends('layouts.app')

@section('title', 'About')

@section('content')
<section class="bb-about-page">
  <section class="bb-about-editorial" id="about-brand">
    <div class="bb-about-editorial-head">
      <h1>What is<br>Bits&amp;Bobbins?</h1>
      <p>Bits&amp;Bobbins is a playful ecommerce space for tiny treasures, soft everyday gifts, and thoughtful product finds. We keep browsing clear, checkout simple, and every customer touchpoint connected from product page to feedback.</p>
    </div>

    <div class="bb-about-wide-image">
      <img src="{{ asset('assets/frontend/img/Home Page/login/about image.jfif') }}" alt="Bits&Bobbins about image">
    </div>

  </section>

  <section class="bb-about-feedback-section" id="about-feedback">
    <div class="bb-about-feedback-head">
      <h2>Customer words</h2>
    </div>

    <div class="bb-about-feedback-slider-wrap">
      <button type="button" class="bb-about-feedback-arrow prev" data-about-feedback-slide="prev" aria-label="Previous feedback">
        <i class="fa fa-arrow-left"></i>
      </button>

      <div class="bb-about-feedback-slider">
        @forelse ($feedback as $item)
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
        @empty
          <article class="bb-about-feedback-card">
            <p>No customer feedback has been submitted yet.</p>
            <div>
              <span class="bb-about-avatar"><i class="bi bi-chat-square-heart"></i></span>
              <span><strong>Bits&amp;Bobbins</strong><small>Feedback will appear here after purchases.</small></span>
            </div>
          </article>
        @endforelse
      </div>

      <button type="button" class="bb-about-feedback-arrow next" data-about-feedback-slide="next" aria-label="Next feedback">
        <i class="fa fa-arrow-right"></i>
      </button>
    </div>
  </section>

  <section class="bb-about-contact-section" id="about-contact">
    <h2>Contact</h2>
    <div class="bb-about-contact-grid">
      <a href="mailto:support@bitsandbobbins.test">
        <i class="bi bi-envelope"></i>
        <span>Customer Support</span>
        <strong>support@bitsandbobbins.test</strong>
      </a>
      <a href="mailto:orders@bitsandbobbins.test">
        <i class="bi bi-bag-check"></i>
        <span>Orders</span>
        <strong>orders@bitsandbobbins.test</strong>
      </a>
      <a href="mailto:feedback@bitsandbobbins.test">
        <i class="bi bi-chat-square-heart"></i>
        <span>Feedback</span>
        <strong>feedback@bitsandbobbins.test</strong>
      </a>
    </div>
  </section>

  <section class="bb-about-faq-section" id="about-faq">
    <h2>FAQ</h2>
    <form class="store-faq-search" method="GET" action="{{ route('store.about') }}#about-faq">
      <input name="q" value="{{ request('q') }}" placeholder="Search FAQ">
      <button><i class="bi bi-search"></i></button>
    </form>
    <div class="store-panel">
      @forelse ($faqs as $faq)
        <details class="store-faq-item">
          <summary>{{ $faq->question }}</summary>
          <p>{{ $faq->answer }}</p>
        </details>
      @empty
        <p>No FAQs found.</p>
      @endforelse
    </div>
  </section>
</section>

<script>
  (() => {
    document.querySelectorAll('[data-about-feedback-slide]').forEach((button) => {
      button.addEventListener('click', () => {
        const slider = button.closest('.bb-about-feedback-slider-wrap')?.querySelector('.bb-about-feedback-slider');
        if (!slider) return;

        const direction = button.dataset.aboutFeedbackSlide === 'next' ? 1 : -1;
        const distance = slider.querySelector('.bb-about-feedback-card')?.offsetWidth ?? 360;
        slider.scrollBy({ left: direction * (distance + 16), behavior: 'smooth' });
      });
    });
  })();
</script>
@endsection

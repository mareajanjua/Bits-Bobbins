@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
<section class="bb-page-hero bb-page-hero--about bb-faq-page-hero">
  <div class="container">
    <div class="bb-page-hero-inner">
      <h1 class="bb-faq-page-title"><span class="bb-faq-sticker">FAQ</span>Got Questions?</h1>
      <p>Find quick answers about orders, payment, delivery, returns, replacements, and Bits&Bobbins products.</p>
    </div>
  </div>
</section>

<section class="bb-page-section bb-about-section bb-about-faq-section" id="faq">
  <div class="container">
    <form method="GET" action="{{ route('store.faq') }}" class="bb-faq-page-search" role="search">
      <input type="search" name="q" value="{{ $search }}" placeholder="Search FAQs..." aria-label="Search FAQs">
      <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
      @if ($search !== '')
        <a href="{{ route('store.faq') }}" class="bb-faq-search-clear">Clear</a>
      @endif
    </form>
    @if ($search !== '')
      <p class="bb-faq-search-status">Showing {{ $faqs->count() }} result(s) for "{{ $search }}".</p>
    @endif

    <div class="bb-faq-layout bb-faq-layout--stacked">
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

    <div class="bb-faq-contact bb-faq-contact--bottom">
      <h3>Still got<br>questions?</h3>
      <a href="{{ route('store.about') }}#about-contact" class="bb-outline-btn">Contact Us</a>
    </div>
  </div>
</section>
@endsection

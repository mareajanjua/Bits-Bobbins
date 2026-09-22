@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
<div class="store-page-head"><h1>FAQ</h1><p>Search customer questions managed by admin.</p></div>
<form class="store-faq-search" method="GET"><input name="q" value="{{ request('q') }}" placeholder="Search FAQ"><button><i class="bi bi-search"></i></button></form>
<div class="store-panel">
  @forelse ($faqs as $faq)
    <details class="store-faq-item"><summary>{{ $faq->question }}</summary><p>{{ $faq->answer }}</p></details>
  @empty
    <p>No FAQs found.</p>
  @endforelse
</div>
@endsection

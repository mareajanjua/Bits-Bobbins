@extends('layouts.admin')

@section('title', $record ? 'Edit FAQ' : 'Add FAQ')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-question-circle"></i> {{ $record ? 'Edit FAQ' : 'Add FAQ' }}</h1>
    <p class="page-subtitle">Create simple question-answer pairs for customer support.</p>
  </div>
</div>

<div class="card dashboard-overview-card">
  <form method="POST" action="{{ $record ? route('admin.faq.update', $record->faq_id) : route('admin.faq.store') }}" class="row g-3">
    @csrf
    <div class="col-md-8">
      <label class="form-label-custom">Question</label>
      <input name="question" class="form-control-custom" value="{{ old('question', $record->question ?? '') }}" required>
    </div>
    <div class="col-md-4">
      <label class="form-label-custom">Display Order</label>
      <input name="display_order" type="number" min="0" class="form-control-custom" value="{{ old('display_order', $record->display_order ?? 0) }}">
    </div>
    <div class="col-12">
      <label class="form-label-custom">Answer</label>
      <textarea name="answer" rows="5" class="form-control-custom" required>{{ old('answer', $record->answer ?? '') }}</textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn-custom btn-custom-primary">Save FAQ</button>
      <a href="{{ route('admin.faq.index') }}" class="btn-custom btn-custom-light">Cancel</a>
    </div>
  </form>
</div>
@endsection

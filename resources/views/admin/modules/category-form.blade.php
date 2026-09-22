@extends('layouts.admin')

@section('title', ($record ?? null) ? 'Edit Category' : 'Add Category')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi bi-tags"></i> {{ ($record ?? null) ? 'Edit Category' : 'Add Category' }}</h1>
    <p class="page-subtitle">Create or update a simple product category. The 2-digit code controls generated product IDs.</p>
  </div>
</div>

<div class="card dashboard-overview-card">
  <form method="POST" action="{{ ($record ?? null) ? route('admin.categories.update', $record->category_code) : route('admin.categories.store') }}" class="row g-3">
    @csrf
    @if ($errors->any())
      <div class="col-12">
        <div class="alert alert-danger mb-0">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      </div>
    @endif
    <div class="col-md-4">
      <label class="form-label-custom">2-Digit Category Code</label>
      <input type="text" name="category_code" class="form-control-custom" value="{{ old('category_code', $record->category_code ?? '') }}" maxlength="2" pattern="[0-9]{2}" placeholder="01" @readonly($record ?? null) @required(! ($record ?? null))>
      @if ($record ?? null)
        <small class="text-muted-green">Code is locked because products use it inside their 7-digit Product ID.</small>
      @endif
    </div>
    <div class="col-md-8">
      <label class="form-label-custom">Category Name</label>
      <input type="text" name="category_name" class="form-control-custom" value="{{ old('category_name', $record->category_name ?? '') }}" placeholder="Dolls" required>
    </div>
    <div class="col-12">
      <button type="submit" class="btn-custom btn-custom-primary">{{ ($record ?? null) ? 'Update Category' : 'Save Category' }}</button>
      <a href="{{ route('admin.categories.index') }}" class="btn-custom btn-custom-light">Cancel</a>
    </div>
  </form>
</div>

@if ($record ?? null)
  <div class="card dashboard-overview-card mt-3">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
      <div>
        <h2 class="card-title mb-1"><i class="bi bi-diagram-3"></i> Subcategories</h2>
        <p class="page-subtitle mb-0">Optional. Add only where needed, like Gift Articles, Greeting Cards, or Files.</p>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.categories.subcategories.store', $record->category_code) }}" class="row g-2 mb-3">
      @csrf
      <div class="col-md-8">
        <input type="text" name="subcategory_name" class="form-control-custom" placeholder="Gift Articles">
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn-custom btn-custom-primary w-100"><i class="bi bi-plus-lg"></i>Add Subcategory</button>
      </div>
    </form>

    @if ($subcategories->isNotEmpty())
      <div class="table-responsive">
        <table class="table table-custom dashboard-table mb-0">
          <thead><tr><th>Subcategory</th><th>Action</th></tr></thead>
          <tbody>
            @foreach ($subcategories as $subcategory)
              <tr>
                <td>{{ $subcategory->subcategory_name }}</td>
                <td>
                  <form method="POST" action="{{ route('admin.subcategories.destroy', $subcategory->subcategory_id) }}" class="inline-action-form">
                    @csrf
                    @method('DELETE')
                    <button class="table-btn-action delete" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="dashboard-empty-state dashboard-empty-state-table">
        <div class="dashboard-empty-icon"><i class="bi bi-diagram-3"></i></div>
        <strong>No subcategories added for this category.</strong>
      </div>
    @endif
  </div>
@endif
@endsection

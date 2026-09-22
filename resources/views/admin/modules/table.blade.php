@extends('layouts.admin')

@section('title', $title)

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="bi {{ $icon }}"></i> {{ $title }}</h1>
    <p class="page-subtitle">{{ $subtitle }}</p>
  </div>
  @isset($actionLabel)
    <a href="{{ $actionRoute }}" class="btn-custom btn-custom-primary"><i class="bi bi-plus-lg"></i>{{ $actionLabel }}</a>
  @endisset
</div>

@isset($filters)
  {!! $filters !!}
@endisset

<div class="card dashboard-overview-card">
  @if ($rows->isNotEmpty())
    <div class="table-responsive recent-orders-scroll">
      <table class="table table-custom dashboard-table recent-orders-table mb-0">
        <thead>
          <tr>
            @foreach ($columns as $column)
              <th>{{ $column }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach ($rows as $row)
            <tr>
              @foreach ($row as $cell)
                <td>{!! $cell !!}</td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="dashboard-empty-state dashboard-empty-state-table">
      <div class="dashboard-empty-icon"><i class="bi {{ $icon }}"></i></div>
      <strong>{{ $empty }}</strong>
    </div>
  @endif
</div>
@endsection

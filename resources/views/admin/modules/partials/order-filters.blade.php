<div class="card dashboard-overview-card mb-3">
  <form class="row g-3 align-items-end" method="GET">
    <div class="col-md-3">
      <label class="form-label-custom">Date From</label>
      <input type="date" name="date_from" class="form-control-custom" value="{{ request('date_from') }}">
    </div>
    <div class="col-md-3">
      <label class="form-label-custom">Date To</label>
      <input type="date" name="date_to" class="form-control-custom" value="{{ request('date_to') }}">
    </div>
    <div class="col-md-3">
      <label class="form-label-custom">Delivery Type</label>
      <select name="delivery_code" class="form-select-custom">
        <option value="">All Delivery Types</option>
        @foreach ($deliveryTypes as $type)
          <option value="{{ $type->delivery_code }}" @selected(request('delivery_code') === $type->delivery_code)>{{ $type->delivery_code }} - {{ $type->delivery_name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label-custom">Status</label>
      <select name="status" class="form-select-custom">
        <option value="">All Statuses</option>
        @foreach (['placed', 'payment_pending', 'payment_cleared', 'dispatched', 'delivered', 'cancelled'] as $status)
          <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12">
      <button class="btn-custom btn-custom-primary" type="submit"><i class="bi bi-funnel"></i>Apply Filters</button>
      <a href="{{ route('admin.orders.index') }}" class="btn-custom btn-custom-light">Reset</a>
    </div>
  </form>
</div>

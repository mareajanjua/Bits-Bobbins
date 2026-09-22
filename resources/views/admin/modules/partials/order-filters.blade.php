<div class="card dashboard-overview-card mb-3">
  <form class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label-custom">Date From</label>
      <input type="date" class="form-control-custom">
    </div>
    <div class="col-md-3">
      <label class="form-label-custom">Date To</label>
      <input type="date" class="form-control-custom">
    </div>
    <div class="col-md-3">
      <label class="form-label-custom">Delivery Type</label>
      <select class="form-select-custom">
        <option>All Delivery Types</option>
        @foreach ($deliveryTypes as $type)
          <option>{{ $type->delivery_code }} - {{ $type->delivery_name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label-custom">Status</label>
      <select class="form-select-custom">
        <option>All Statuses</option>
        <option>Placed</option>
        <option>Payment Pending</option>
        <option>Payment Cleared</option>
        <option>Dispatched</option>
        <option>Delivered</option>
        <option>Cancelled</option>
      </select>
    </div>
  </form>
</div>

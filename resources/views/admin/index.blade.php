@extends ('layouts.admin')

@section ('main')
    <div class="row">
    <div class="col-sm-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">{{ __('messages.dashboard.welcome', ['name' => Auth::user()->name]) }}</h5>
          <p class="card-text">{{ __('messages.dashboard.last_connection', ['date' => Auth::user()->last_seen_at->tz(\App\Models\Settings\General::getValue('app', 'timezone'))->format(\App\Models\Settings\General::getValue('app', 'date_format'))]) }}</p>
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Special title treatment</h5>
          <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
    </div>
    </div>
@endsection

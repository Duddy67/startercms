@extends ('layouts.admin')

@section ('main')
    <div class="row">
    <div class="col-sm-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">{{ __('messages.dashboard.welcome', ['name' => Auth::user()->name]) }}</h5>
          <p class="card-text">{{ __('messages.dashboard.last_connection', ['date' => $general::getFormattedDate(Auth::user()->last_seen_at)]) }}</p>
          <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
      </div>
    </div>
    <div class="card col-sm-6" style="width: 18rem;">
        <div class="card-header">
          Featured
        </div>
        <ul class="list-group list-group-flush">
            @foreach ($users as $user)
                <li class="list-group-item"><span class="font-weight-bold mr-4">{{ $user->name }}</span> {{ $general::getFormattedDate($user->last_logged_in_at) }}</li>
            @endforeach
        </ul>
      </div>
    </div>
@endsection

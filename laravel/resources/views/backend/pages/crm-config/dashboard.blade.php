@extends('backend.layouts.app')
@section('title', 'CRM Dashboard Cards')
@section('admin-content')
<div class="max-w-3xl mx-auto p-6 space-y-6">
<h1 class="text-2xl font-semibold">CRM Dashboard Cards</h1>
<p>Choose cards and their labels. Changes are saved in the database and appear when you reload the dashboard. Appointment count is customers with saved appointment details.</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<form method="POST" action="{{ route('admin.crm-config.update-dashboard') }}" class="space-y-4">
@csrf @method('PUT')
@foreach($dashboardCards as $i=>$card)
<div>
<input type="hidden" name="cards[{{ $i }}][key]" value="{{ $card['key'] }}">
<input type="hidden" name="cards[{{ $i }}][enabled]" value="0">
<label><input type="checkbox" name="cards[{{ $i }}][enabled]" value="1" @checked($card['enabled'])> {{ $card['key'] }}</label>
<input aria-label="Card label" class="form-control" name="cards[{{ $i }}][label]" value="{{ $card['label'] }}" maxlength="80" required>
</div>
@endforeach
<button type="submit" class="btn-primary">Save dashboard cards</button>
</form>
</div>
@endsection

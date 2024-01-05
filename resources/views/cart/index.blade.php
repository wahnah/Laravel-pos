@extends('layouts.admin')

@section('title', 'Open POS')

@section('content')

<style>
    .centered-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh; /* Make the container full height of the viewport */
    }
</style>

@if (!App\Models\ProductSnapshot::whereDate('date', today())->exists())
    <div class="centered-container">
         <!-- Add this line -->
        <a href="{{ route('open-day') }}" class="btn btn-primary">Open Day</a>
    </div>
@else
<p>Press F2 to activate scanner</p>
    <div id="cart"></div>
@endif
@endsection

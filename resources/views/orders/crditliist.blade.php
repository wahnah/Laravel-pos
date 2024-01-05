@extends('layouts.admin')

@section('title', 'Credit List')
@section('content-header', 'Credit List')
@section('content-actions')

@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-5"></div>
            {{-- <div class="col-md-7">
    <form action="{{route('orders.index')}}">
        <div class="row">
            <div class="col-md-5">
                <input type="date" name="start_date" class="form-control" value="{{request('start_date')}}" />
            </div>
            <div class="col-md-5">
                <input type="date" name="end_date" class="form-control" value="{{request('end_date')}}" />
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary" type="submit">Submit</button>
            </div>
        </div>
    </form>
</div> --}}
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Owed Amount</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                <tr>

                    <td>{{$order->getCustomerName()}}</td>
                    <td>{{ config('settings.currency_symbol') }} {{$order->totalPrice}}</td>
                    <td>{{$order->updated_at}}</td>
                    <td>
                        <form action="{{ route('orders.updatee', ['order_id' => $order->id]) }}" method="post">
                            @csrf
                            @method('post') {{-- Use "patch" method for updating --}}
                            <input type="hidden" name="credit" value="1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-dollar"></i>
                            </button>
                        </form>
                        @if(auth()->user()->role == 0)
                            <!-- Add additional content for role 0 if needed -->
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection


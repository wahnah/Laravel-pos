@extends('layouts.admin')

@section('title', 'Orders List')
@section('content-header', 'Order List')
@section('content-actions')
@auth
        @if(auth()->user()->role == 0)
    <a href="{{route('orders.credlist')}}" class="btn btn-primary">Credit</a>
    <a href="{{route('orders.getAllOrderItems')}}" class="btn btn-primary">Sales Reports</a>
    <a href="{{route('stock.populateStockSheet')}}" class="btn btn-primary">Stock Sheet</a>
    {{--<a href="{{route('stock.dailyReport')}}" class="btn btn-primary">daily report</a>--}}

    @else

    <a href="{{route('orders.credlist')}}" class="btn btn-primary">Credit</a>

    @endif
@endauth
@endsection

@section('content')
<div class="card">
    <div class="card-body">
    <div class="col-md-10" >

                <form action="{{route('orders.index')}}">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Enter or Scan Order ID" />
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary" type="submit">Search</button>
                        </div>
                    </div>
                </form>
            </div>
</div>
</div>
<br>
<div class="card">
    <div class="card-body">

        <div class="row">
            <div class="col-md-5"></div>


        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Total</th>
                    <th>Received Amount</th>
                    <th>Status</th>
                    <th>To Pay</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                <tr>
                    <td>{{$order->id}}</td>
                    <td>{{$order->getCustomerName()}}</td>
                    <td>{{ config('settings.currency_symbol') }} {{$order->formattedTotal()}}</td>
                    <td>{{ config('settings.currency_symbol') }} {{$order->formattedReceivedAmount()}}</td>
                    <td>
                        @if($order->receivedAmount() == 0)
                            <span class="badge badge-danger">Not Paid</span>
                        @elseif($order->receivedAmount() < $order->total())
                            <span class="badge badge-warning">Partial</span>
                        @elseif($order->receivedAmount() == $order->total())
                            <span class="badge badge-success">Paid</span>
                        @elseif($order->receivedAmount() > $order->total())
                            <span class="badge badge-info">Change</span>
                        @endif
                    </td>
                    <td>{{config('settings.currency_symbol')}} {{number_format($order->total() - $order->receivedAmount(), 2)}}</td>
                    <td>{{$order->created_at}}</td>
                    <td>
                        <a href="{{ route('orders.edit', ['order_id' => $order->id]) }}" class="btn btn-primary"><i
                                class="fas fa-edit"></i></a>
                        @if(auth()->user()->role == 0)

                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
        {{ $orders->render() }}
    </div>
</div>
@endsection


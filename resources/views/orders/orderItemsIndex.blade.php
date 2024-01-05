@extends('layouts.admin')

@section('title', 'Product List')
@section('content-header', 'Sold Items List')
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card product-list">
    <div class="card-body">
        <div class="row">
            <div class="col-md-5"></div>
            <div class="col-md-7">
                <form action="{{route('orders.report')}}">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="date" name="start_date" class="form-control" value="{{request('start_date')}}" />
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary" type="submit">Print</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Sold Qty</th>
                    <th>Sold Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orderItems as $history)
                <tr>
                    <td>{{ $history->id }}</td>
                <td>{{ $history->product->name }}</td>
                <td>{{ $history->quantity }}</td>
                <td>{{ $history->created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $orderItems->render() }}
    </div>
</div>
@endsection



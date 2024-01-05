@extends('layouts.admin')

@section('title', 'Product List')
@section('content-header', 'Stock Sheet')
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card product-list">
    <div class="card-body">
        <div class="row">
            <div class="col-md-7"></div>
            <div class="col-md-5">
                <form action="{{route('stock.populateStockSheetReport')}}">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{request('start_date')}}" />
                            @error('start_date')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
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

                    <th>Date</th>
                    <th>Product Name</th>
                    <th>Open Qty</th>
                    <th>Restock Qty</th>
                    <th>Sold Qty</th>
                    <th>Amount Sold</th>
                    <th>Close Qty</th>

                </tr>
            </thead>
            <tbody>
                @foreach ($stocksheetData as $entry)
                    <tr>
                        <td>{{ $entry['date'] }}</td>
                        <td>{{ $entry['product_id'] }}</td>
                        <td>{{ $entry['open_qty'] }}</td>
                        <td>{{ $entry['restock_qty'] }}</td>
                        <td>{{ $entry['sold_qty'] }}</td>
                        <td>{{ $entry['amount_sold'] }}</td>
                        <td>{{ $entry['close_qty'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection



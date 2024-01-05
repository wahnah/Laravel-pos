@extends('layouts.reportlay')

@section('title', 'Stock Sheet')
@section('content-header', 'Stock Sheet')
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .print-buttons {

    }

    @media print {
        .print-buttons {
            display: none !important;
        }
    }
</style>
@endsection
@section('content')
<div class="card product-list">
    <div class="card-header">
        <h3 class="card-title">Stock Sheet for {{ $selectedDate }}</h3>
    </div>
    <div class="card-body">
        <div class="print-buttons mb-2">
            <button class="btn btn-primary" id="printButton">Print</button>
            <a href="{{ route('stock.populateStockSheet') }}" class="btn btn-secondary">Back</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Open Qty</th>
                    <th>Restock Qty</th>
                    <th>profit</th>
                    <th>Sold Qty</th>
                    <th>Amount Sold</th>
                    <th>Close Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stocksheetData as $entry)
                    <tr>
                        <td>{{ $entry['product_id'] }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ $entry['product_price'] }}</td>
                        <td>{{ $entry['open_qty'] }}</td>
                        <td>{{ $entry['restock_qty'] }}</td>
                        <td>{{config('settings.currency_symbol') }}{{ number_format($entry['profit'], 2) }}</td>
                        <td>{{ $entry['sold_qty'] }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($entry['amount_sold'], 2) }}</td>
                        <td>{{ $entry['close_qty'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>| Total Profit :</th>
                    <th>{{ config('settings.currency_symbol') }} {{ number_format($totalProfit, 2) }}</th>
                    <th>| Total :</th>
                    <th>{{ config('settings.currency_symbol') }} {{ number_format($totalAmount, 2) }}</th>
                    <th></th>
                </tr>
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Owed Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    <tr>

                        <td>{{$order->getCustomerName()}}</td>
                        <td>{{ config('settings.currency_symbol') }} {{$order->totalPrice}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </tfoot>
        </table>


    </div>
</div>

<script>
    // Function to redirect to restock.index
    function redirectToRestockIndex() {
        window.location.href = '{{ route('stock.populateStockSheet') }}'; // Replace 'your_redirect_url_here' with the actual URL
    }

    // Event listener for afterprint event
    window.addEventListener("afterprint", function () {
        // This code runs after the print dialogue is closed
        // Close the tab automatically (optional)
        window.close();

        // Redirect to restock.index
        redirectToRestockIndex();
    });

    // Function to handle the print button click
    document.getElementById('printButton').addEventListener('click', function () {
        // Trigger the print dialogue automatically when the print button is clicked
        window.print();
    });
</script>
@endsection

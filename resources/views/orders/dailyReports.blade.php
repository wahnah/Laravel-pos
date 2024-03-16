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
        <h3 class="card-title">Daily report for</h3>
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
                    <th>Close Qty (Previous Day)</th>
                    <th>Pre Close Qty (Current Day)</th>
                    <th>Shotage</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($displayData as $data)
                    <tr>
                        <td>{{ $data['product_name'] }}</td>
                        <td>{{ $data['close_qty_current_day'] }}</td>
                        <td>{{ $data['pre_close_qty_previous_day'] }}</td>
                        <td>{{ $data['unsigned_difference'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

</br>
</br>
    <table class="table">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>


    </br>
</br>
<h1>Daily Summary</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Total Amount</th>
                <th>Total Amount from Orders</th>
                <th>Difference</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $total_amount }}</td>
                <td>{{ $total_amount_all }}</td>
                <td>{{ $diff }}</td>
            </tr>
        </tbody>
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

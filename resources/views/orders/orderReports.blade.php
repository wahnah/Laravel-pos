@extends('layouts.reportlay')

@section('title', 'Stock Sheet')
@section('content-header', 'Stock Sheet')
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card product-list">
    <div class="card-header">
        <h3 class="card-title">Sales Report for {{ $start_date }}</h3>
    </div>
    <div class="card-body">
        <table class="table">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Sold Qty</th>
                <th>Sold Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($restocks as $history)
            <tr>

                <td>{{ $history->product->name }}</td>
                <td>{{ $history->total_quantity }}</td>
                <td>{{ $history->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <script>
        // Function to redirect to restock.index
        function redirectToRestockIndex() {
            window.location.href = '{{ route('orders.getAllOrderItems') }}'; // Replace 'your_redirect_url_here' with the actual URL
        }

        // Event listener for afterprint event
        window.addEventListener("afterprint", function() {
            // This code runs after the print dialogue is closed
            // Close the tab automatically (optional)
            window.close();

            // Redirect to restock.index
            redirectToRestockIndex();
        });

        // Trigger the print dialogue automatically when the page is loaded
        window.print();
    </script>
</div>
</div>
@endsection

@extends('layouts.reportlay')

@section('title', 'Daily report')
@section('content-header', 'Daily Report')
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    /* Custom CSS for tables */
    .table {
        font-size: 14px;
 /* Adjust font size as needed */
    }

    .table th,
    .table td {
        padding: 8px; /* Adjust padding as needed */
    }

    .table th {
        background-color: #f0f0f0; /* Header background color */
    }

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6; /* Border color */
    }

    .print-buttons {}

    @media print {
        .print-buttons {
            display: none !important;
        }
    }

    /* Style for the pie chart canvas */
    #pieChart {
        max-width: 400px; /* Adjust the maximum width as needed */
        margin-top: 20px;
    }
</style>
@endsection
@section('content')
<div class= "card">
    <div class="card-header">
        <h2 class="card-title">Daily report for</h2>
    </div>

</div>

<div class="print-buttons mb-2">

    <button class="btn btn-primary" id="printButton">Print</button>
    <a href="{{ route('stock.populateStockSheet') }}" class="btn btn-secondary">Back</a>

</div>
<div class="row">
    <div class="col-md-4">
        <div class="card product-list">

            <div class="card-body">
                <h1>Sales Summary</h1>
                <table class="table mb-2">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Close Qty (Previous Day)</th>
                            <th>Pre Close Qty (Current Day)</th>
                            <th>Shortage</th>
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
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card product-list">
            <div class="card-body">
                <h1>Product Summary</h1>
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
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card product-list">
            <div class="card-body">
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
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    // Data for the pie chart (you need to replace this with your actual data)
    var pieChartData = {
        labels: ["Red", "Blue", "Yellow", "Green", "Purple", "Orange"],
        datasets: [{
            data: [12, 19, 3, 5, 2, 3], // Sample data
            backgroundColor: [
                "#FF6384",
                "#36A2EB",
                "#FFCE56",
                "#4CAF50",
                "#9C27B0",
                "#FF9800"
            ]
        }]
    };

    // Get the canvas element
    var ctx = document.getElementById('pieChart').getContext('2d');

    // Create the pie chart
    var pieChart = new Chart(ctx, {
        type: 'pie',
        data: pieChartData,
        options: {
            // Add your chart options here
        }
    });
</script>
@endsection

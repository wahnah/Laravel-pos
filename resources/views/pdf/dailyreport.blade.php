<!doctype html>

    <html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Daily Report</title>
    </head>
    <body>
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

<div class= "card">
    <div class="card-header">
        <h2 class="card-title">Daily report for</h2>
    </div>

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
                            <th>System Close Qty (Prev Day)</th>
                            <th>Physical Close Qty (Cur Day)</th>
                            <th>Shortage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($displayData as $data)
                        <tr>
                            <td style="text-align: center;">{{ $data['product_name'] }}</td>
                            <td style="text-align: center;">{{ $data['close_qty_current_day'] }}</td>
                            <td style="text-align: center;">{{ $data['pre_close_qty_previous_day'] }}</td>
                            <td style="text-align: center;">{{ $data['unsigned_difference'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <br>
    <br>
    <div class="col-md-4">
        <div class="card product-list">
            <div class="card-body">
                <h1>Product Summary (Qty below 10)</h1>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Current Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                        <tr>
                            <td style="text-align: center;">{{ $product->name }}</td>
                            <td style="text-align: center;">{{ $product->quantity }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <br>
    <br>
    <div class="col-md-4">
        <div class="card product-list">
            <div class="card-body">
                <h1>Daily Money Summary</h1>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Total Amount(Cashier)</th>
                            <th>Total Amount (system)</th>
                            <th>Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">{{ $total_amount }}</td>
                            <td style="text-align: center;">{{ $total_amount_all }}</td>
                            <td style="text-align: center;">{{ $diff }}</td>
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
</body>
</html>

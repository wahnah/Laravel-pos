@php
$totalPrice = 0; // Initialize total price variable
@endphp
<div id="container">
    <div class="header">
        <div class="logo">
            <!-- Place your logo here -->
            <img src="logo.png" alt="Logo" width="60" height="60">
        </div>
        <h1>{{config('settings.app_name')}}</h1>
    </div>
    <div class="info">
        <p>Contact Us</p>
        <p>
            Address: {{config('settings.address')}}<br>
            Email: {{config('settings.email')}}<br>
            Phone: {{config('settings.phone')}}
        </p>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderItems as $orderitem)
            <tr>
                <td>{{$orderitem->product->name}}</td>
                <td>{{$orderitem->quantity}}</td>
                <td>{{$orderitem->product->price}}</td>
                <td>{{$orderitem->price}}</td>
            </tr>
            @php
    $totalPrice += $orderitem->price; // Add the current item's price to the total
    @endphp
            @endforeach
            <tr>
                <td colspan="3">Total</td>
                <td>ZK {{$totalPrice}}</td>
            </tr>
        </tbody>

    </table>
    <br />
    Customer Name :  {{$custFN}} {{$custLN}}
    <br />
    <br />
    Signeture :  ......................................
    <div class="footer">
        <p>&copy; {{config('settings.app_name')}}</p>
        <p>Serial: {{ rand(100000000, 999999999) }}</p>
        <p>{{ date('d/m/y H:i') }}</p>
    </div>
</div>
<!-- Add a print button -->


<script>
window.addEventListener("afterprint", function() {
    // This code runs after the print dialogue is closed
    // Close the tab automatically
    window.close();
});

// Trigger the print dialogue automatically when the page is loaded
window.print();
</script>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        width: 80mm; /* Set the paper width */
        margin: auto;
    }
    #container {
        padding: 10px;
    }
    .header {
        text-align: center;
        font-size: 20px;
    }
    .info {
        font-size: 14px;
        text-align: center;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .table th, .table td {
        border: 1px solid #ddd;
        padding: 5px;
        text-align: center;
    }
    .table th {
        background-color: #f2f2f2;
    }
    .total {
        font-size: 16px;
        text-align: right;
        margin-top: 10px;
    }
    .footer {
        font-size: 12px;
        text-align: center;
        margin-top: 20px;
    }
</style>



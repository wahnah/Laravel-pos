@php
$totalPrice = 0; // Initialize total price variable
@endphp
<div id="container">
    <div class="header">
        Tax Invoice
    </div>
    <div class="info">
        <h3>{{config('settings.app_name')}}</h3>
        
        <p>
            Address: {{config('settings.address')}}<br>
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
                <td>{{ number_format($orderitem->price, 2) }}</td>
            </tr>
            @php
    $totalPrice += $orderitem->price; // Add the current item's price to the total
    @endphp
            @endforeach
            <tr>
                <td colspan="3">Total</td>
                <td>{{ number_format($totalPrice, 2) }}</td>
            </tr>
        </tbody>
    </table>
    <br />
    Customer Name :  {{$custFN}} {{$custLN}}
    <br />
    <br />
    Signature :  ......................................
    <br>
    <br>
    <div class="contain">
        {!! DNS1D::getBarcodeHTML("$order_id", 'CODABAR',2,50) !!}
        <div style="text-align: center; margin-top: 3px;">
    {{ $order_id }}
</div>
    </div>

    <div class="footer">
        <p>&copy; {{config('settings.app_name')}}</p>
        <p>{{ date('d/m/y H:i') }}</p>
        

    </div>
</div>
<!-- Add a print button -->


<script>
    window.addEventListener("load", function() {
  // Store the order ID for later use
  var orderId = '{{ $order_id }}';

  // Print the receipt twice before redirecting
  var numPrints = 2;
  for (var i = 0; i < numPrints; i++) {
    window.print();
  }

  // Redirect back to the previous page after printing
  setTimeout(function() {
    window.location.href = document.referrer; // Go back to the previous page
  }, 2000); // Delay redirect for 2 seconds (adjust as needed)
});
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
        background-color: black;
        color: white;
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
        border: 1px solid black;
        padding: 5px;
        text-align: center;
    }
    .table th {
        background-color: black;
        color: white;
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
    .contain {
            margin: 83px; /* Center the content horizontally */
            margin-top: 5px;
            margin-bottom: 5px; /* Adjust top margin as needed */
        }
</style>



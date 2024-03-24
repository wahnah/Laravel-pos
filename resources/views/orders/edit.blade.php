@extends('layouts.admin')

@section('title', 'Orders Options')
@section('content-header', 'Order Options')
@section('content')
<div class="card">
    <div class="card-body">
        <div class="print-buttons mb-2 d-flex justify-content-end">
            @if($order->credit == 0)
                <a href="{{ route('orders.creceipt', ['order_id' => $order_id]) }}" class="btn btn-primary" id="printButton">Reprint Receipt</a>
            @else
                <a href="{{ route('orders.oreceipt', ['order_id' => $order_id]) }}" class="btn btn-primary" id="printButton">Reprint Receipt</a>
            @endif
        </div>

        <form method="POST" action="{{ route('orders.update', ['order_id' => $order_id]) }}">
            @csrf

            <div class="form-group">
                <label for="customer_id">Customer Name</label>
                <select name="customer_id" class="form-control @error('customer_id') is-invalid @enderror" id="customer_id">
                    {{-- Assuming you have a 'customers' table with 'id', 'first_name', and 'last_name' columns --}}
                    <option value="" disabled selected>Select a customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : ''}}>
                            {{ $customer->first_name }} {{ $customer->last_name }}
                        </option>
                    @endforeach
                    <option value="new">Add New Customer</option>
                </select>
                @error('customer_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>


            <div class="form-group">
                <label for="credit">Order status</label>
                <select name="credit" class="form-control @error('credit') is-invalid @enderror" id="scredit">
                    <option value="0" {{ old('credit', $order->credit) == 0 ? 'selected' : ''}}>Credit</option>
                    <option value="1" {{ old('credit', $order->credit) == 1 ? 'selected' : ''}}>Paid</option>
                </select>
                @error('credit')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Update</button>
        </form>

    </div>
</div>

<br/>
<br/>
<br/>
<div class="card">
    <div class="card-body">
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="updateOrderItemForm" method="POST" action="'{{ route('orderItem.update', ['orderItemId' => 'orderItemId']) }}'">
                @csrf
                <!-- Hidden input field to hold the order item ID -->
                <input type="hidden" name="order_item_id" id="order_item_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Product Name</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Quantity adjustment -->
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<h4>Order Items</h4>
@auth
        @if(auth()->user()->role == 0)
<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Item</th>
        <th>Qty</th>
        <th>Unit Price</th>
        <th>Subtotal</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      @foreach($orderItems as $orderItem)
        <tr>
          <td>{{$orderItem->product->name}}</td>
          <td>
            <form action="{{ route('orderItem.update', ['orderItemId' => $orderItem->id]) }}" method="POST">
              @csrf
              @method('POST')
              <input type="number" name="newQuantity" value="{{$orderItem->quantity}}" class="form-control form-control-sm">
              <button type="submit" class="btn btn-sm btn-success">Update</button>
            </form>
          </td>
          <td>{{$orderItem->product->price}}</td>
          <td>{{$orderItem->price}}</td>
          <td>
            <form action="{{ route('orderItem.delete', ['orderItemId' => $orderItem->id]) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
            <a href="{{ route('cart.minicart', ['orderItemId' => $orderItem->id])  }}" class="btn btn-sm btn-primary">Swap</a>
          </td>
        </tr>
      @endforeach
      <tr>
        <td colspan="3">Total</td>
        <td>ZK {{$totalPrice}}</td>
        <td></td>
      </tr>
    </tbody>
  </table>
</div>
@else
<div class="table-responsive">
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
      @foreach($orderItems as $orderItem)
        <tr>
          <td>{{$orderItem->product->name}}</td>
          <td>
            <form action="{{ route('orderItem.update', ['orderItemId' => $orderItem->id]) }}" method="POST">
              @csrf
              @method('POST')
              <input type="number" name="newQuantity" value="{{$orderItem->quantity}}" class="form-control form-control-sm">
              <button type="submit" class="btn btn-sm btn-success">Update</button>
            </form>
          </td>
          <td>{{$orderItem->product->price}}</td>
          <td>{{$orderItem->price}}</td>

        </tr>
      @endforeach
      <tr>
        <td colspan="3">Total</td>
        <td>ZK {{$totalPrice}}</td>
        <td></td>
      </tr>
    </tbody>
  </table>
</div>
@endif
@endauth
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    document.getElementById('customer_id').addEventListener('change', function () {
        var selectedOption = this.options[this.selectedIndex].value;
        var newCustomerModal = document.getElementById('newCustomerModal');

        if (selectedOption === 'new') {
            var order_id = '{{ $order_id }}'; // Echo the $order_id variable
            var customerShowRoute = '{{ route('orders.createCust', ['order_id' => ':order_id']) }}';
            customerShowRoute = customerShowRoute.replace(':order_id', order_id); // Replace placeholder with order_id
            window.location.href = customerShowRoute;
        }
    });

</script>
@endsection

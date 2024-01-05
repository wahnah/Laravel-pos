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

            <!-- Modal for new customer -->
            <div class="modal fade" id="newCustomerModal" tabindex="-1" role="dialog" aria-labelledby="newCustomerModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newCustomerModalLabel">Add New Customer</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Add fields for new customer (e.g., first_name, last_name, etc.) -->
                            <div class="form-group">
                                <label for="new_first_name">First Name</label>
                                <input type="text" name="new_first_name" class="form-control" id="new_first_name" value="{{ old('new_first_name') }}">
                            </div>
                            <div class="form-group">
                                <label for="new_last_name">Last Name</label>
                                <input type="text" name="new_last_name" class="form-control" id="new_last_name" value="{{ old('new_last_name') }}">
                            </div>
                            <!-- Add more fields as needed -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button id="saveChangesButton" type="button" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </div>
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




        <br/>
        <br/>
        <br/>

        <h4>Order Items</h4> <!-- Added table title -->
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
                @endforeach
                <tr>
                    <td colspan="3">Total</td>
                    <td>ZK {{$totalPrice}}</td>
                </tr>
            </tbody>
        </table>
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
                $('#newCustomerModal').modal('show');
            } else {
                $('#newCustomerModal').modal('hide');
            }
        });

        $('#saveChangesButton').on('click', function () {
    var modalForm = $('#newCustomerModal').find('form');
    var formData = modalForm.serialize(); // Serialize the form data
    var csrfToken = $('meta[name="csrf-token"]').attr('content'); // Get the CSRF token

    $.ajax({
        type: 'POST',
        url: '{{ route('customers.store') }}',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the headers
        },
        success: function (response) {
            console.log(response);
            $('#newCustomerModal').modal('hide');
        },
        error: function (error) {
            console.error(error);
        }
    });
});
    </script>
@endsection

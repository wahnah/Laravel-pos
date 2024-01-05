@extends('layouts.admin')

@section('title', 'Edit Product')
@section('content-header', 'Edit Product')

@section('content')

<div class="card">
    <div class="card-body">

        @if(auth()->user()->role == 0)

        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name"
                    placeholder="Name" value="{{ old('name', $product->name) }}">
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="orderprice">Order Price</label>
                <input type="text" name="orderprice" class="form-control @error('orderprice') is-invalid @enderror" id="orderprice"
                    placeholder="orderprice" value="{{ old('orderprice') }}">
                @error('orderprice')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                    id="description"
                    placeholder="description">{{ old('description', $product->description) }}</textarea>
                @error('description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="image">Image</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="image" id="image">
                    <label class="custom-file-label" for="image">Choose file</label>
                </div>
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="barcode">Barcode</label>
                <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
                    id="barcode" placeholder="barcode" value="{{ old('barcode', $product->barcode) }}">
                @error('barcode')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">Sale Price</label>
                <input type="text" name="price" class="form-control @error('price') is-invalid @enderror" id="price"
                    placeholder="price" value="{{ old('price', $product->price) }}">
                @error('price')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="text" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                    id="quantity" placeholder="Quantity" value="{{ old('quantity', $product->quantity) }}" readonly>
                @error('quantity')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                    <option value="1" {{ old('status', $product->status) === 1 ? 'selected' : ''}}>Active</option>
                    <option value="0" {{ old('status', $product->status) === 0 ? 'selected' : ''}}>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">Update</button>
        </form>
        @else
        <label for="quantity">Current Quantity</label>
        <input type="text" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                    id="quantity" placeholder="Quantity" value="{{ old('quantity', $product->quantity) }}" readonly>
                    @endif
    </div>
    <!-- Button to open the restock modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#restockModal">
    Restock
</button>

<!-- Restock Modal -->
<div class="modal fade" id="restockModal" tabindex="-1" role="dialog" aria-labelledby="restockModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restockModalLabel">Restock Quantity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="restockForm">
                    <div class="form-group">
                        <label for="restockQuantity">Enter Quantity</label>
                        <input type="text" name="restockQuantity" class="form-control" id="restockQuantity"
                            placeholder="Quantity">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitRestock">Submit</button>
            </div>
        </div>
    </div>
</div>



</div>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        // Handle the click event of the "Restock" button
        $("#submitRestock").click(function () {
            // Get the restock quantity from the input field
            var restockQuantity = $("#restockQuantity").val();

            // Perform any validation you need here
            if (!restockQuantity || isNaN(restockQuantity)) {
                alert("Please enter a valid quantity.");
                return;
            }

            // Close the modal
            $("#restockModal").modal("hide");

            // Send the data to the 'restock.restockProduct' route using AJAX
            var productId = "{{ $product->id }}"; // Replace with the actual product ID
            var url = "{{ route('restock.restockProduct', ['productId' => ':productId', 'quantityAdded' => ':quantityAdded']) }}"
    .replace(':productId', productId)
    .replace(':quantityAdded', restockQuantity);

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    _token: "{{ csrf_token() }}",
                },
                success: function (data) {
                    // Handle the success response if needed
                    console.log(data);

                    location.reload();
                },
                error: function (error) {
                    // Handle the error response if needed
                    console.log(error);
                },
            });
        });
    });
</script>
@endsection

@section('js')
<script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(document).ready(function () {
        bsCustomFileInput.init();
    });
</script>




@endsection

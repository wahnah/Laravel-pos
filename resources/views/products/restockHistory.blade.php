@extends('layouts.admin')

@section('title', 'Product List')
@section('content-header', 'Restock History')
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card product-list">
    <div class="card-body">
        <div class="row">
            <div class="col-md-5"></div>
            <div class="col-md-7">
                <form action="{{route('restock.report')}}">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{request('start_date')}}" />
                            @error('start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary" type="submit">Print</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Restocked Quantity</th>
                    <th>Restock Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($restocks as $history)
                <tr>
                    <td>{{$history->id}}</td>
                    <td>{{$history->product->name}}</td>
                    <td>{{$history->quantity_added}}</td>
                    <td>{{$history->restock_date}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $restocks->render() }}
    </div>
</div>
@endsection



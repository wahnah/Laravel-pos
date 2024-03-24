@extends('layouts.admin')

@section('title', 'Swap Cart')

@section('content')

<div id="minicart" data-orderitemid="{{ $orderItemId }}"></div>

@endsection

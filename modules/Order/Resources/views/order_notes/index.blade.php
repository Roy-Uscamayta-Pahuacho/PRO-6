@extends('tenant.layouts.app')

@section('content')

    <tenant-order-notes-index :type-user="{{json_encode(Auth::user()->type)}}" :soap-company="{{ json_encode($soap_company) }}"></tenant-order-notes-index>

@endsection

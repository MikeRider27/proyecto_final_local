@extends('principal')
@section('contenido')
    @include('plantilla.comprobante', [
        'tipo' => 'compra',
        'entidades' => $proveedores,
        'productos' => $productos,
        'siguiente' => $siguiente,
    ])
@endsection

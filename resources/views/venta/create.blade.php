@extends('principal')
@section('contenido')
    @include('plantilla.comprobante', [
        'tipo' => 'venta',
        'entidades' => $clientes,
        'productos' => $productos,
        'siguiente' => $siguiente,
    ])
@endsection

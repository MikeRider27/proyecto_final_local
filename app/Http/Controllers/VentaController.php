<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Venta;
use App\DetalleVenta;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use DB;


class VentaController extends Controller
{
    //
    
    public function index(Request $request){
      
        if($request){
 
            $sql=trim($request->get('buscarTexto'));
            $ventas=Venta::join('clientes','ventas.idcliente','=','clientes.id')
            ->join('users','ventas.idusuario','=','users.id')
            ->join('detalle_ventas','ventas.id','=','detalle_ventas.idventa')
             ->select('ventas.id','ventas.tipo_identificacion',
             'ventas.num_venta','ventas.fecha_venta','ventas.impuesto',
             'ventas.estado','ventas.total','clientes.nombre as cliente','users.nombre')
            ->where('ventas.num_venta','LIKE','%'.$sql.'%')
            ->orderBy('ventas.id','desc')
            ->groupBy('ventas.id','ventas.tipo_identificacion',
            'ventas.num_venta','ventas.fecha_venta','ventas.impuesto',
            'ventas.estado','ventas.total','clientes.nombre','users.nombre')
            ->paginate(8);
             
 
            return view('venta.index',["ventas"=>$ventas,"buscarTexto"=>$sql]);
            //return $ventas;
        }
      
 
     }
 
        public function create(){
 
             /*listar las clientes en ventana modal*/
             $clientes=DB::table('clientes')->orderBy('nombre')->get();

             /*listar los productos con stock disponible*/
             $productos=DB::table('productos as prod')
             ->select(DB::raw("CONCAT(prod.codigo,' ',prod.nombre) AS producto"),'prod.id','prod.codigo','prod.nombre','prod.stock','prod.precio_venta')
             ->where('prod.condicion','=','1')
             ->where('prod.stock','>','0')
             ->orderBy('prod.nombre')
             ->get();

             // Número sugerido para el comprobante (se puede modificar)
             $siguiente = sprintf('V-%06d', (Venta::max('id') ?? 0) + 1);

             return view('venta.create',["clientes"=>$clientes,"productos"=>$productos,"siguiente"=>$siguiente]);
  
        }
 
         public function store(Request $request){

             $request->validate([
                 'id_cliente' => 'required|integer|exists:clientes,id',
                 'tipo_identificacion' => 'required|in:FACTURA,TICKET',
                 'num_venta' => 'required|string|max:10',
                 'id_producto' => 'required|array|min:1',
                 'id_producto.*' => 'integer|exists:productos,id',
                 'cantidad.*' => 'required|integer|min:1',
                 'precio_venta.*' => 'required|numeric|min:0',
                 'descuento.*' => 'required|numeric|min:0|max:99.99',
             ], [
                 'id_producto.required' => 'Agregá al menos un producto a la venta.',
             ]);

             try{

                 DB::beginTransaction();
                 $mytime= Carbon::now();

                 $venta = new Venta();
                 $venta->idcliente = $request->id_cliente;
                 $venta->idusuario = \Auth::user()->id;
                 $venta->tipo_identificacion = $request->tipo_identificacion;
                 $venta->num_venta = $request->num_venta;
                 $venta->fecha_venta = $mytime;
                 $venta->impuesto = "0.20";
                 // Precio y total salen de la base, no del navegador: subtotal con descuentos + 20 % de impuesto
                 $precios = DB::table('productos')->whereIn('id', $request->id_producto)->pluck('precio_venta', 'id');
                 $subtotal = 0;
                 foreach ($request->id_producto as $i => $idProducto) {
                     $bruto = $request->cantidad[$i] * $precios[$idProducto];
                     $subtotal += $bruto - $bruto * $request->descuento[$i] / 100;
                 }
                 $venta->total = round($subtotal * 1.20, 2);
                 $venta->estado = 'Registrado';
                 $venta->save();
 
                 $id_producto=$request->id_producto;
                 $cantidad=$request->cantidad;
                 $descuento=$request->descuento;
 
                 
                 //Recorro todos los elementos
                 $cont=0;
     
                  while($cont < count($id_producto)){
 
                     $detalle = new DetalleVenta();
                     /*enviamos valores a las propiedades del objeto detalle*/
                     /*al idcompra del objeto detalle le envio el id del objeto venta, que es el objeto que se ingresó en la tabla ventas de la bd*/
                     /*el id es del registro de la venta*/
                     $detalle->idventa = $venta->id;
                     $detalle->idproducto = $id_producto[$cont];
                     $detalle->cantidad = $cantidad[$cont];
                     $detalle->precio = $precios[$id_producto[$cont]];
                     $detalle->descuento = $descuento[$cont];

                     // Se bloquea el producto y se verifica que alcance el stock antes de descontarlo
                     $producto = DB::table('productos')->where('id', $detalle->idproducto)->lockForUpdate()->first();
                     if ($producto->stock < $detalle->cantidad) {
                         throw new \DomainException("Stock insuficiente para «{$producto->nombre}» (disponible: {$producto->stock}).");
                     }

                     $detalle->save();
                     DB::table('productos')->where('id', $detalle->idproducto)->decrement('stock', $detalle->cantidad);

                     $cont=$cont+1;
                 }

                 DB::commit();

             } catch(\DomainException $e){

                 DB::rollBack();
                 return Redirect::back()->withInput()->with('error', $e->getMessage());

             } catch(\Throwable $e){

                 DB::rollBack();
                 report($e);
                 return Redirect::back()->withInput()->with('error', 'No se pudo registrar la venta. Intentá de nuevo.');
             }

             return Redirect::to('venta')->with('success', 'Venta registrada correctamente.');
         }
 
         public function show($id){
 
             //dd($id);
             //dd($request->all());
            
             /*mostrar venta*/
 
             //$id = $request->id;
             $venta = Venta::join('clientes','ventas.idcliente','=','clientes.id')
             ->join('detalle_ventas','ventas.id','=','detalle_ventas.idventa')
             ->select('ventas.id','ventas.tipo_identificacion',
             'ventas.num_venta','ventas.fecha_venta','ventas.impuesto',
             'ventas.estado','clientes.nombre',
             DB::raw('sum(detalle_ventas.cantidad*precio - detalle_ventas.cantidad*precio*descuento/100) as total')
             )
             ->where('ventas.id','=',$id)
             ->orderBy('ventas.id', 'desc')
             ->groupBy('ventas.id','ventas.tipo_identificacion',
             'ventas.num_venta','ventas.fecha_venta','ventas.impuesto',
             'ventas.estado','clientes.nombre')
             ->first();
 
             /*mostrar detalles*/
             $detalles = DetalleVenta::join('productos','detalle_ventas.idproducto','=','productos.id')
             ->select('detalle_ventas.cantidad','detalle_ventas.precio','detalle_ventas.descuento','productos.nombre as producto')
             ->where('detalle_ventas.idventa','=',$id)
             ->orderBy('detalle_ventas.id', 'desc')->get();
             
             return view('venta.show',['venta' => $venta,'detalles' =>$detalles]);
         }
         
         public function destroy(Request $request){
 
             DB::transaction(function () use ($request) {
                 $venta = Venta::lockForUpdate()->findOrFail($request->id_venta);
                 if ($venta->estado === 'Anulado') {
                     return; // ya estaba anulada: no se devuelve el stock dos veces
                 }
                 $venta->estado = 'Anulado';
                 $venta->save();

                 // La mercadería de la venta anulada vuelve al stock
                 foreach (DetalleVenta::where('idventa', $venta->id)->get() as $detalle) {
                     DB::table('productos')->where('id', $detalle->idproducto)->increment('stock', $detalle->cantidad);
                 }
             });
             return Redirect::to('venta')->with('success', 'Venta anulada.');
 
        }
 
         public function pdf(Request $request,$id){
         
             $venta = Venta::join('clientes','ventas.idcliente','=','clientes.id')
             ->join('users','ventas.idusuario','=','users.id')
             ->join('detalle_ventas','ventas.id','=','detalle_ventas.idventa')
             ->select('ventas.id','ventas.tipo_identificacion',
             'ventas.num_venta','ventas.created_at','ventas.impuesto',
             'ventas.estado',DB::raw('sum(detalle_ventas.cantidad*precio - detalle_ventas.cantidad*precio*descuento/100) as total'),'clientes.nombre','clientes.tipo_documento','clientes.num_documento',
             'clientes.direccion','clientes.email','clientes.telefono','users.usuario')
             ->where('ventas.id','=',$id)
             ->orderBy('ventas.id', 'desc')
             ->groupBy('ventas.id','ventas.tipo_identificacion',
             'ventas.num_venta','ventas.created_at','ventas.impuesto',
             'ventas.estado','clientes.nombre','clientes.tipo_documento','clientes.num_documento',
             'clientes.direccion','clientes.email','clientes.telefono','users.usuario')
             ->take(1)->get();
 
             $detalles = DetalleVenta::join('productos','detalle_ventas.idproducto','=','productos.id')
             ->select('detalle_ventas.cantidad','detalle_ventas.precio','detalle_ventas.descuento',
             'productos.nombre as producto')
             ->where('detalle_ventas.idventa','=',$id)
             ->orderBy('detalle_ventas.id', 'desc')->get();
 
             $numventa=Venta::select('num_venta')->where('id',$id)->get();
             
             $pdf= \PDF::loadView('pdf.venta',['venta'=>$venta,'detalles'=>$detalles]);
             return $pdf->download('venta-'.$numventa[0]->num_venta.'.pdf');
         }
 
     
}

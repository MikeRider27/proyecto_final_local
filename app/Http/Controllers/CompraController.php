<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Compra;
use App\DetalleCompra;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use DB;


class CompraController extends Controller
{

    public function index(Request $request){
      
        if($request){
        
            $sql=trim($request->get('buscarTexto'));
            $compras=Compra::join('proveedores','compras.idproveedor','=','proveedores.id')
            ->join('users','compras.idusuario','=','users.id')
            ->join('detalle_compras','compras.id','=','detalle_compras.idcompra')
             ->select('compras.id','compras.tipo_identificacion',
             'compras.num_compra','compras.fecha_compra','compras.impuesto',
             'compras.estado','compras.total','proveedores.nombre as proveedor','users.nombre')
            ->where('compras.num_compra','LIKE','%'.$sql.'%')
            ->orderBy('compras.id','desc')
            ->groupBy('compras.id','compras.tipo_identificacion',
            'compras.num_compra','compras.fecha_compra','compras.impuesto',
            'compras.estado','compras.total','proveedores.nombre','users.nombre')
            ->paginate(8);
             
 
            return view('compra.index',["compras"=>$compras,"buscarTexto"=>$sql]);
            
            //return $compras;
        }
      
 
     }
 
        public function create(){
 
             /*listar las proveedores en ventana modal*/
             $proveedores=DB::table('proveedores')->orderBy('nombre')->get();

             /*listar los productos en ventana modal*/
             $productos=DB::table('productos as prod')
             ->select(DB::raw("CONCAT(prod.codigo,' ',prod.nombre) AS producto"),'prod.id','prod.codigo','prod.nombre','prod.stock','prod.precio_venta')
             ->where('prod.condicion','=','1')->orderBy('prod.nombre')->get();

             // Número sugerido para el comprobante (se puede modificar)
             $siguiente = sprintf('C-%06d', (Compra::max('id') ?? 0) + 1);

             return view('compra.create',["proveedores"=>$proveedores,"productos"=>$productos,"siguiente"=>$siguiente]);
  
        }
 
         public function store(Request $request){
         
             $request->validate([
                 'id_proveedor' => 'required|integer|exists:proveedores,id',
                 'tipo_identificacion' => 'required|in:FACTURA,TICKET',
                 'num_compra' => 'required|string|max:10',
                 'id_producto' => 'required|array|min:1',
                 'id_producto.*' => 'integer|exists:productos,id',
                 'cantidad.*' => 'required|integer|min:1',
                 'precio_compra.*' => 'required|numeric|min:0',
             ], [
                 'id_producto.required' => 'Agregá al menos un producto a la compra.',
             ]);

             try{

                 DB::beginTransaction();

                 $mytime= Carbon::now();

                 $compra = new Compra();
                 $compra->idproveedor = $request->id_proveedor;
                 $compra->idusuario = \Auth::user()->id;
                 $compra->tipo_identificacion = $request->tipo_identificacion;
                 $compra->num_compra = $request->num_compra;
                 $compra->fecha_compra = $mytime;
                 $compra->impuesto = '0.20';
                 // El total se calcula en el servidor (no se confía en el enviado por el navegador): subtotal + 20 % de impuesto
                 $subtotal = 0;
                 foreach ($request->id_producto as $i => $_) {
                     $subtotal += $request->cantidad[$i] * $request->precio_compra[$i];
                 }
                 $compra->total = round($subtotal * 1.20, 2);
                 $compra->estado = 'Registrado';
                 $compra->save();
 
                 $id_producto=$request->id_producto;
                 $cantidad=$request->cantidad;
                 $precio=$request->precio_compra;
                
 
                 
                 //Recorro todos los elementos
                 $cont=0;
     
                  while($cont < count($id_producto)){
 
                     $detalle = new DetalleCompra();
                     /*enviamos valores a las propiedades del objeto detalle*/
                     /*al idcompra del objeto detalle le envio el id del objeto compra, que es el objeto que se ingresó en la tabla compras de la bd*/
                     $detalle->idcompra = $compra->id;
                     $detalle->idproducto = $id_producto[$cont];
                     $detalle->cantidad = $cantidad[$cont];
                     $detalle->precio = $precio[$cont];
                     $detalle->save();

                     // La compra ingresa mercadería al stock
                     DB::table('productos')->where('id', $detalle->idproducto)->increment('stock', $detalle->cantidad);

                     $cont=$cont+1;
                 }

                 DB::commit();

             } catch(\Throwable $e){

                 DB::rollBack();
                 report($e);
                 return Redirect::back()->withInput()->with('error', 'No se pudo registrar la compra. Intentá de nuevo.');
             }

             return Redirect::to('compra')->with('success', 'Compra registrada correctamente.');
         }
 
         public function show($id){
 
             //dd($id);
            
             /*mostrar compra*/
 
             //$id = $request->id;
             $compra = Compra::join('proveedores','compras.idproveedor','=','proveedores.id')
             ->join('detalle_compras','compras.id','=','detalle_compras.idcompra')
             ->select('compras.id','compras.tipo_identificacion',
             'compras.num_compra','compras.fecha_compra','compras.impuesto',
             'compras.estado',DB::raw('sum(detalle_compras.cantidad*precio) as total'),'proveedores.nombre')
             ->where('compras.id','=',$id)
             ->orderBy('compras.id', 'desc')
             ->groupBy('compras.id','compras.tipo_identificacion',
             'compras.num_compra','compras.fecha_compra','compras.impuesto',
             'compras.estado','proveedores.nombre')
             ->first();
 
             /*mostrar detalles*/
             $detalles = DetalleCompra::join('productos','detalle_compras.idproducto','=','productos.id')
             ->select('detalle_compras.cantidad','detalle_compras.precio','productos.nombre as producto')
             ->where('detalle_compras.idcompra','=',$id)
             ->orderBy('detalle_compras.id', 'desc')->get();
             
             return view('compra.show',['compra' => $compra,'detalles' =>$detalles]);
         }
         
         public function destroy(Request $request){
 
     
                 DB::transaction(function () use ($request) {
                     $compra = Compra::lockForUpdate()->findOrFail($request->id_compra);
                     if ($compra->estado === 'Anulado') {
                         return; // ya estaba anulada: no se descuenta el stock dos veces
                     }
                     $compra->estado = 'Anulado';
                     $compra->save();

                     // Se retira del stock lo que había ingresado esta compra
                     foreach (DetalleCompra::where('idcompra', $compra->id)->get() as $detalle) {
                         DB::table('productos')->where('id', $detalle->idproducto)->decrement('stock', $detalle->cantidad);
                     }
                 });
                 return Redirect::to('compra')->with('success', 'Compra anulada.');
 
     }
 
         public function pdf(Request $request,$id){
         
             $compra = Compra::join('proveedores','compras.idproveedor','=','proveedores.id')
             ->join('users','compras.idusuario','=','users.id')
             ->join('detalle_compras','compras.id','=','detalle_compras.idcompra')
             ->select('compras.id','compras.tipo_identificacion',
             'compras.num_compra','compras.created_at','compras.impuesto',DB::raw('sum(detalle_compras.cantidad*precio) as total'),
             'compras.estado','proveedores.nombre','proveedores.tipo_documento','proveedores.num_documento',
             'proveedores.direccion','proveedores.email','proveedores.telefono','users.usuario')
             ->where('compras.id','=',$id)
             ->orderBy('compras.id', 'desc')
             ->groupBy('compras.id','compras.tipo_identificacion',
             'compras.num_compra','compras.created_at','compras.impuesto',
             'compras.estado','proveedores.nombre','proveedores.tipo_documento','proveedores.num_documento',
             'proveedores.direccion','proveedores.email','proveedores.telefono','users.usuario')
             ->take(1)->get();
 
             $detalles = DetalleCompra::join('productos','detalle_compras.idproducto','=','productos.id')
             ->select('detalle_compras.cantidad','detalle_compras.precio',
             'productos.nombre as producto')
             ->where('detalle_compras.idcompra','=',$id)
             ->orderBy('detalle_compras.id', 'desc')->get();
 
             $numcompra=Compra::select('num_compra')->where('id',$id)->get();
             
             $pdf= \PDF::loadView('pdf.compra',['compra'=>$compra,'detalles'=>$detalles]);
             return $pdf->download('compra-'.$numcompra[0]->num_compra.'.pdf');
         }
 
 
}

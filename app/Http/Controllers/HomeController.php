<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        
        $comprasmes=DB::select("SELECT trim(to_char(c.fecha_compra,'FMMonth')) as mes, sum(c.total) as totalmes from compras c where c.estado='Registrado' group by trim(to_char(c.fecha_compra,'FMMonth')) order by min(extract(month from c.fecha_compra)) desc limit 12");

        $ventasmes=DB::select("SELECT trim(to_char(v.fecha_venta,'FMMonth')) as mes, sum(v.total) as totalmes from ventas v where v.estado='Registrado' group by trim(to_char(v.fecha_venta,'FMMonth')) order by min(extract(month from v.fecha_venta)) desc limit 12");

        $ventasdia=DB::select("SELECT to_char(v.fecha_venta,'DD/MM/YYYY') as dia, sum(v.total) as totaldia from ventas v where v.estado='Registrado' group by v.fecha_venta order by extract(day from v.fecha_venta) desc limit 15");

        $productosvendidos=DB::select("SELECT p.nombre as producto, sum(dv.cantidad) as cantidad from productos p inner join detalle_ventas dv on p.id=dv.idproducto inner join ventas v on dv.idventa=v.id where v.estado='Registrado' and extract(year from v.fecha_venta)=extract(year from current_date) group by p.nombre order by sum(dv.cantidad) desc limit 10");

        $totales=DB::select("SELECT (select coalesce(sum(c.total),0) from compras c where c.fecha_compra::date=current_date and c.estado='Registrado') as totalcompra, (select coalesce(sum(v.total),0) from ventas v where v.fecha_venta::date=current_date and v.estado='Registrado') as totalventa");

            return view('home',["comprasmes"=>$comprasmes,"ventasmes"=>$ventasmes,"ventasdia"=>$ventasdia,"productosvendidos"=>$productosvendidos,"totales"=>$totales]);
    
        }
}

<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración (ficticios) para el Sistema de Compras y Ventas.
 *
 *   ./vendor/bin/sail artisan db:seed --class=DemoSeeder
 *
 * Vacía y vuelve a cargar categorías, productos, proveedores, clientes, compras
 * y ventas. Los usuarios se crean/actualizan sin borrar los existentes.
 * Es determinista: cada ejecución genera los mismos datos (relativos a la fecha actual).
 */
class DemoSeeder extends Seeder
{
    private const IVA = 0.20;   // el sistema guarda impuesto = 0.20 y suma 20 % al total

    public function run()
    {
        if (app()->environment('production')) {
            $this->command->error('DemoSeeder no se ejecuta en producción.');
            return;
        }

        mt_srand(2711);
        $ahora = Carbon::now();

        DB::transaction(function () use ($ahora) {
            DB::statement('TRUNCATE detalle_ventas, ventas, detalle_compras, compras, productos, categorias, clientes, proveedores RESTART IDENTITY CASCADE');

            $usuarios    = $this->usuarios($ahora);
            $productos   = $this->catalogo($ahora);
            $proveedores = $this->personas('proveedores', $this->datosProveedores(), $ahora);
            $clientes    = $this->personas('clientes', $this->datosClientes(), $ahora);

            $stock = array_fill_keys(array_keys($productos), 0);
            $this->simular($productos, $proveedores, $clientes, $usuarios, $stock, $ahora);

            // Los movimientos se insertan directo en la base (sin pasar por los controladores),
            // así que el stock final se escribe acá, coherente con lo comprado y vendido.
            foreach ($stock as $id => $cantidad) {
                DB::table('productos')->where('id', $id)->update(['stock' => max(0, $cantidad)]);
            }
        });

        $this->command->table(['Tabla', 'Registros'], collect([
            'categorias', 'productos', 'proveedores', 'clientes', 'users', 'compras', 'ventas',
        ])->map(fn ($t) => [$t, DB::table($t)->count()])->all());
        $this->command->info('Usuarios demo: vendedor / comprador — contraseña: demo1234');
    }

    // ------------------------------------------------------------------ usuarios

    private function usuarios(Carbon $ahora): array
    {
        $lista = [
            ['usuario' => 'ucevito',   'nombre' => 'Administrador',     'idrol' => 1, 'password' => null],
            ['usuario' => 'vendedor',  'nombre' => 'Laura Benítez',     'idrol' => 2, 'password' => 'demo1234'],
            ['usuario' => 'comprador', 'nombre' => 'Diego Ayala',       'idrol' => 3, 'password' => 'demo1234'],
            ['usuario' => 'rgimenez',  'nombre' => 'Rocío Giménez',     'idrol' => 2, 'password' => 'demo1234'],
        ];

        $ids = [];
        foreach ($lista as $u) {
            $existe = DB::table('users')->where('usuario', $u['usuario'])->first();
            $datos = [
                'nombre' => $existe->nombre ?? $u['nombre'],
                'tipo_documento' => 'CEDULA',
                'num_documento' => $existe->num_documento ?? (string) mt_rand(1500000, 6500000),
                'direccion' => $existe->direccion ?? 'Asunción',
                'telefono' => $existe->telefono ?? $this->telefono(),
                'email' => $existe->email ?? $u['usuario'].'@demo.com.py',
                'condicion' => 1,
                'idrol' => $u['idrol'],
                'updated_at' => $ahora,
            ];
            if ($u['password'] && ! $existe) {
                $datos['password'] = bcrypt($u['password']);
            }
            if (! $existe) {
                $datos['created_at'] = $ahora;
                if (! $u['password']) {
                    $datos['password'] = bcrypt('ab/*06011952');
                }
            }
            DB::table('users')->updateOrInsert(['usuario' => $u['usuario']], $datos);
            $ids[$u['usuario']] = DB::table('users')->where('usuario', $u['usuario'])->value('id');
        }

        return [
            'compras' => [$ids['ucevito'], $ids['comprador'], $ids['comprador']],
            'ventas'  => [$ids['ucevito'], $ids['vendedor'], $ids['vendedor'], $ids['rgimenez']],
        ];
    }

    // ------------------------------------------------------------------ catálogo

    /** @return array<int, array{precio: float, costo: float, rotacion: int}> */
    private function catalogo(Carbon $ahora): array
    {
        // categoría => [descripción, [[nombre, precio_venta en Gs, rotación 1-5], ...]]
        $catalogo = [
            'Bebidas' => ['Gaseosas, aguas, jugos y bebidas en general', [
                ['Coca-Cola 2 L', 15000, 5], ['Agua mineral sin gas 2 L', 7500, 5], ['Jugo Pulp naranja 1 L', 9500, 4],
                ['Cerveza Pilsen lata 473 ml', 8500, 5], ['Agua tónica 1,5 L', 11000, 2], ['Energizante 250 ml', 12500, 3],
            ]],
            'Almacén' => ['Productos secos de consumo diario', [
                ['Yerba mate Pajarito 1 kg', 28000, 5], ['Arroz tipo 1 1 kg', 9800, 4], ['Fideo tallarín 500 g', 6500, 4],
                ['Aceite de girasol 900 ml', 17500, 4], ['Azúcar blanca 1 kg', 7800, 4], ['Harina de trigo 000 1 kg', 6900, 3],
                ['Poroto rojo 500 g', 11500, 2], ['Galletitas surtidas 400 g', 13000, 3],
            ]],
            'Lácteos' => ['Leches, quesos y derivados', [
                ['Leche entera 1 L', 7200, 5], ['Queso Paraguay 1 kg', 38000, 3], ['Yogur bebible frutilla 1 L', 14500, 3],
                ['Manteca 200 g', 16500, 2], ['Dulce de leche 500 g', 18000, 2],
            ]],
            'Limpieza' => ['Artículos de limpieza para el hogar', [
                ['Detergente líquido 750 ml', 13500, 3], ['Lavandina 1 L', 6500, 3], ['Jabón en polvo 800 g', 21000, 3],
                ['Desinfectante de pisos 900 ml', 15500, 2], ['Esponja doble uso x3', 8500, 2],
            ]],
            'Higiene personal' => ['Cuidado e higiene personal', [
                ['Papel higiénico x4', 14000, 4], ['Shampoo 400 ml', 26500, 2], ['Jabón de tocador x3', 12500, 3],
                ['Pasta dental 90 g', 11000, 3], ['Desodorante aerosol 150 ml', 24000, 2],
            ]],
            'Electrónica' => ['Accesorios y pequeños electrónicos', [
                ['Cargador USB-C 20 W', 95000, 2], ['Auriculares inalámbricos', 185000, 1], ['Pendrive 64 GB', 75000, 1],
                ['Cable HDMI 2 m', 45000, 1], ['Pilas AA x4', 22000, 3],
            ]],
            'Ferretería' => ['Herramientas e insumos de ferretería', [
                ['Cinta aisladora', 9000, 2], ['Foco LED 12 W', 18500, 3], ['Destornillador plano', 25000, 1],
                ['Candado de bronce 40 mm', 55000, 1],
            ]],
        ];

        $productos = [];
        $codigo = 7840001000;
        foreach ($catalogo as $categoria => [$descripcion, $items]) {
            $idCategoria = DB::table('categorias')->insertGetId([
                'nombre' => $categoria, 'descripcion' => $descripcion, 'condicion' => 1,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
            foreach ($items as [$nombre, $precio, $rotacion]) {
                $id = DB::table('productos')->insertGetId([
                    'idcategoria' => $idCategoria,
                    'codigo' => (string) $codigo++,
                    'nombre' => $nombre,
                    'precio_venta' => $precio,
                    'stock' => 0,
                    'condicion' => 1,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
                // Costo de compra: entre 62 % y 78 % del precio de venta, redondeado a 50 Gs.
                $costo = round($precio * mt_rand(62, 78) / 100 / 50) * 50;
                $productos[$id] = ['precio' => (float) $precio, 'costo' => (float) $costo, 'rotacion' => $rotacion];
            }
        }

        // Un producto inactivo para ver ese estado en la interfaz.
        DB::table('productos')->where('nombre', 'Agua tónica 1,5 L')->update(['condicion' => 0]);

        return $productos;
    }

    // ------------------------------------------------------------------ proveedores / clientes

    private function personas(string $tabla, array $filas, Carbon $ahora): array
    {
        $ids = [];
        foreach ($filas as [$nombre, $tipo, $doc, $ciudad]) {
            $ids[] = DB::table($tabla)->insertGetId([
                'nombre' => $nombre,
                'tipo_documento' => $tipo,
                'num_documento' => $doc,
                'direccion' => $this->direccion($ciudad),
                'telefono' => $this->telefono(),
                'email' => $this->email($nombre),
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
        return $ids;
    }

    private function datosProveedores(): array
    {
        return [
            ['Distribuidora Guaraní S.A.', 'CEDULA', '80012345-6', 'Asunción'],
            ['Alimentos del Este S.R.L.', 'CEDULA', '80023456-1', 'Ciudad del Este'],
            ['Lácteos Pirapó', 'CEDULA', '80034567-8', 'Encarnación'],
            ['Bebidas del Paraguay S.A.', 'CEDULA', '80045678-3', 'Luque'],
            ['Higiene Total Importadora', 'CEDULA', '80056789-0', 'San Lorenzo'],
            ['TecnoImport PY', 'CEDULA', '80067890-5', 'Asunción'],
            ['Ferretería Mayorista Central', 'CEDULA', '80078901-2', 'Fernando de la Mora'],
            ['Limpieza Express S.A.', 'CEDULA', '80089012-7', 'Lambaré'],
        ];
    }

    private function datosClientes(): array
    {
        $nombres = ['María', 'José', 'Ana', 'Carlos', 'Lucía', 'Juan', 'Sofía', 'Pedro', 'Valeria', 'Luis',
            'Camila', 'Miguel', 'Fernanda', 'Jorge', 'Paola', 'Ricardo', 'Natalia', 'Óscar', 'Gabriela', 'Hugo',
            'Andrea', 'Marcelo', 'Liz', 'Fabián', 'Nilda', 'Rubén', 'Celeste', 'Derlis'];
        $apellidos = ['González', 'Benítez', 'Martínez', 'López', 'Giménez', 'Villalba', 'Duarte', 'Ramírez',
            'Báez', 'Fernández', 'Ortiz', 'Cáceres', 'Rojas', 'Acosta', 'Franco', 'Insfrán', 'Cabrera', 'Aquino'];
        $ciudades = ['Asunción', 'San Lorenzo', 'Luque', 'Capiatá', 'Lambaré', 'Fernando de la Mora',
            'Limpio', 'Ñemby', 'Mariano Roque Alonso', 'Encarnación', 'Ciudad del Este'];

        $filas = [['Consumidor Final', 'CEDULA', '44444401', 'Asunción']];
        $usados = [];
        while (count($filas) < 30) {
            $n = $nombres[mt_rand(0, count($nombres) - 1)].' '.$apellidos[mt_rand(0, count($apellidos) - 1)];
            if (isset($usados[$n])) {
                continue;
            }
            $usados[$n] = true;
            $filas[] = [$n, mt_rand(0, 9) ? 'CEDULA' : 'DNI', (string) mt_rand(1200000, 7200000), $ciudades[mt_rand(0, count($ciudades) - 1)]];
        }
        // Algunas empresas como clientes
        $filas[] = ['Supermercado La Familia', 'CEDULA', '80098765-4', 'Capiatá'];
        $filas[] = ['Despensa Don Pedro', 'CEDULA', '80087654-9', 'Luque'];
        $filas[] = ['Hotel Paraná', 'CEDULA', '80076543-2', 'Encarnación'];

        return $filas;
    }

    // ------------------------------------------------------------------ movimientos

    /**
     * Simula el año día por día: ventas diarias y reposición cuando el stock baja
     * del punto de pedido, para que el stock final sea creíble.
     */
    private function simular(array $productos, array $proveedores, array $clientes, array $usuarios, array &$stock, Carbon $ahora): void
    {
        $ids = array_keys($productos);
        $inicio = $ahora->copy()->subMonths(11)->startOfMonth()->addDays(2);

        // Algunos productos se reponen tarde a propósito, para que haya artículos con stock bajo.
        $descuidados = [$ids[4], $ids[17], $ids[29]];

        // Compra inicial (stock de apertura) un mes antes del período simulado,
        // para que no distorsione el gráfico de los últimos 12 meses.
        foreach (array_chunk($ids, 10) as $i => $lote) {
            $items = [];
            foreach ($lote as $id) {
                $items[] = [$id, $this->stockObjetivo($productos[$id])];
            }
            $this->registrarCompra($proveedores[$i % count($proveedores)], $usuarios, $items, $productos, $inicio->copy()->subMonth()->setTime(9, $i * 10), $stock, 'Registrado');
        }

        $dia = $inicio->copy()->addDays(2);
        while ($dia->lte($ahora)) {
            $esHoy = $dia->isSameDay($ahora);

            // Reposición (por la mañana) cada 3 días, y siempre hoy para que el panel muestre movimiento.
            if ($dia->dayOfYear % 3 === 0 || $esHoy) {
                $items = [];
                foreach ($ids as $id) {
                    $p = $productos[$id];
                    $puntoPedido = (int) ($this->stockObjetivo($p) * 0.35);
                    $postergar = in_array($id, $descuidados, true) && $dia->diffInDays($ahora) < 50;
                    if ($stock[$id] < $puntoPedido && ! $postergar) {
                        $items[] = [$id, $this->stockObjetivo($p) - $stock[$id] + mt_rand(0, 3) * $p['rotacion']];
                    }
                }
                if ($esHoy && ! $items) {
                    $items[] = [$ids[0], 24];
                }
                foreach (array_chunk($items, 8) as $lote) {
                    $estado = mt_rand(1, 30) === 1 ? 'Anulado' : 'Registrado';
                    $fecha = $esHoy ? $this->momentoDeHoy($ahora) : $dia->copy()->setTime(8, mt_rand(0, 59));
                    $this->registrarCompra($proveedores[mt_rand(0, count($proveedores) - 1)], $usuarios, $lote, $productos, $fecha, $stock, $estado);
                }
            }

            // Ventas del día: tendencia creciente a lo largo del año, más a fin de mes y en diciembre.
            $progreso = 1 - $dia->diffInDays($ahora) / 335;
            $base = 2 + (int) round($progreso * 3);
            $extra = ($dia->day >= 25 ? 2 : 0) + ($dia->month === 12 ? 3 : 0) + ($dia->isWeekend() ? 1 : 0);
            $ventasDelDia = mt_rand(1, $base + $extra);
            if ($esHoy) {
                $ventasDelDia = max(3, $ventasDelDia);
            }

            for ($v = 0; $v < $ventasDelDia; $v++) {
                $items = [];
                foreach ($this->elegir($ids, mt_rand(1, 4), $productos) as $id) {
                    $cantidad = mt_rand(1, 1 + $productos[$id]['rotacion']);
                    if ($stock[$id] >= $cantidad) {
                        $items[] = [$id, $cantidad, mt_rand(1, 6) === 1 ? [5, 10][mt_rand(0, 1)] : 0];
                    }
                }
                if (! $items) {
                    continue;
                }
                $cliente = mt_rand(1, 4) === 1 ? $clientes[0] : $clientes[mt_rand(1, count($clientes) - 1)];
                // Hoy nunca se generan movimientos con hora futura.
                $fecha = $esHoy ? $this->momentoDeHoy($ahora) : $dia->copy()->setTime(mt_rand(8, 19), mt_rand(0, 59));
                $estado = mt_rand(1, 30) === 1 ? 'Anulado' : 'Registrado';
                $this->registrarVenta($cliente, $usuarios, $items, $productos, $fecha, $stock, $estado);
            }
            $dia->addDay();
        }
    }

    private function registrarCompra(int $proveedor, array $usuarios, array $items, array $productos, Carbon $fecha, array &$stock, string $estado): void
    {
        static $numero = 0;
        $subtotal = 0;
        foreach ($items as [$id, $cantidad]) {
            $subtotal += $cantidad * $productos[$id]['costo'];
        }

        $idCompra = DB::table('compras')->insertGetId([
            'idproveedor' => $proveedor,
            'idusuario' => $usuarios['compras'][mt_rand(0, count($usuarios['compras']) - 1)],
            'tipo_identificacion' => mt_rand(0, 4) ? 'FACTURA' : 'TICKET',
            'num_compra' => sprintf('C-%06d', ++$numero),
            'fecha_compra' => $fecha,
            'impuesto' => self::IVA,
            'total' => round($subtotal * (1 + self::IVA)),
            'estado' => $estado,
            'created_at' => $fecha, 'updated_at' => $fecha,
        ]);

        foreach ($items as [$id, $cantidad]) {
            DB::table('detalle_compras')->insert([
                'idcompra' => $idCompra, 'idproducto' => $id, 'cantidad' => $cantidad, 'precio' => $productos[$id]['costo'],
            ]);
            if ($estado === 'Registrado') {
                $stock[$id] += $cantidad;
            }
        }
    }

    private function registrarVenta(int $cliente, array $usuarios, array $items, array $productos, Carbon $fecha, array &$stock, string $estado): void
    {
        static $numero = 0;
        $subtotal = 0;
        foreach ($items as [$id, $cantidad, $descuento]) {
            $bruto = $cantidad * $productos[$id]['precio'];
            $subtotal += $bruto - $bruto * $descuento / 100;
        }

        $idVenta = DB::table('ventas')->insertGetId([
            'idcliente' => $cliente,
            'idusuario' => $usuarios['ventas'][mt_rand(0, count($usuarios['ventas']) - 1)],
            'tipo_identificacion' => mt_rand(0, 2) ? 'TICKET' : 'FACTURA',
            'num_venta' => sprintf('V-%06d', ++$numero),
            'fecha_venta' => $fecha,
            'impuesto' => self::IVA,
            'total' => round($subtotal * (1 + self::IVA)),
            'estado' => $estado,
            'created_at' => $fecha, 'updated_at' => $fecha,
        ]);

        foreach ($items as [$id, $cantidad, $descuento]) {
            DB::table('detalle_ventas')->insert([
                'idventa' => $idVenta, 'idproducto' => $id, 'cantidad' => $cantidad,
                'precio' => $productos[$id]['precio'], 'descuento' => $descuento,
            ]);
            if ($estado === 'Registrado') {
                $stock[$id] -= $cantidad;
            }
        }
    }

    // ------------------------------------------------------------------ utilidades

    /** Stock "lleno" de un producto según su rotación. */
    private function stockObjetivo(array $producto): int
    {
        return 15 + $producto['rotacion'] * 22;
    }

    /** Un momento aleatorio entre el inicio del día y ahora. */
    private function momentoDeHoy(Carbon $ahora): Carbon
    {
        $transcurridos = max(1, $ahora->copy()->startOfDay()->diffInMinutes($ahora));
        return $ahora->copy()->startOfDay()->addMinutes(mt_rand(0, $transcurridos));
    }

    /** Elige $n productos distintos, con más probabilidad los de mayor rotación. */
    private function elegir(array $ids, int $n, array $productos): array
    {
        $bolsa = [];
        foreach ($ids as $id) {
            $bolsa = array_merge($bolsa, array_fill(0, $productos[$id]['rotacion'], $id));
        }
        $elegidos = [];
        while (count($elegidos) < min($n, count($ids))) {
            $elegidos[$bolsa[mt_rand(0, count($bolsa) - 1)]] = true;
        }
        return array_keys($elegidos);
    }

    private function telefono(): string
    {
        return sprintf('09%d%d %03d %03d', [7, 8, 9][mt_rand(0, 2)], mt_rand(1, 6), mt_rand(100, 999), mt_rand(100, 999));
    }

    private function direccion(string $ciudad): string
    {
        $calles = ['Av. Mcal. López', 'Av. España', 'Av. Eusebio Ayala', 'Palma', 'Estrella', 'Av. Artigas',
            'Av. San Martín', 'Cerro Corá', 'Tte. Fariña', 'Av. Aviadores del Chaco', 'Gral. Díaz', 'Brasil'];
        return $calles[mt_rand(0, count($calles) - 1)].' '.mt_rand(100, 4500).', '.$ciudad;
    }

    private function email(string $nombre): string
    {
        $base = strtolower(preg_replace('/[^a-z]+/i', '.', iconv('UTF-8', 'ASCII//TRANSLIT', $nombre)));
        return trim($base, '.').'@'.['gmail.com', 'hotmail.com', 'outlook.com', 'tigo.com.py'][mt_rand(0, 3)];
    }
}

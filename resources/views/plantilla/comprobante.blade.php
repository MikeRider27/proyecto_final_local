{{--
    Formulario de comprobante compartido por "Nueva compra" y "Nueva venta".

    Variables:
      $tipo        'compra' | 'venta'
      $entidades   proveedores (compra) o clientes (venta)
      $productos   id, codigo, nombre, stock, precio_venta
      $siguiente   número de comprobante sugerido
--}}
@php
    $esVenta = $tipo === 'venta';
    $cfg = $esVenta
        ? ['titulo' => 'Nueva venta', 'listado' => 'Ventas', 'url' => 'venta', 'entidad' => 'Cliente', 'campoEntidad' => 'id_cliente',
           'campoNumero' => 'num_venta', 'campoPrecio' => 'precio_venta', 'ruta' => route('venta.store'),
           'ayuda' => 'Registrá una venta: el stock de los productos se descuenta al guardar.']
        : ['titulo' => 'Nueva compra', 'listado' => 'Compras', 'url' => 'compra', 'entidad' => 'Proveedor', 'campoEntidad' => 'id_proveedor',
           'campoNumero' => 'num_compra', 'campoPrecio' => 'precio_compra', 'ruta' => route('compra.store'),
           'ayuda' => 'Registrá una compra a proveedor: el stock de los productos aumenta al guardar.'];

    // Si la validación falló, se reconstruye el detalle que el usuario había cargado.
    $itemsPrevios = collect(old('id_producto', []))->map(fn ($id, $i) => [
        'id' => (int) $id,
        'cantidad' => (int) (old('cantidad')[$i] ?? 1),
        'precio' => (float) (old($cfg['campoPrecio'])[$i] ?? 0),
        'descuento' => (float) (old('descuento')[$i] ?? 0),
    ])->values();

    $catalogo = $productos->mapWithKeys(fn ($p) => [$p->id => [
        'id' => $p->id, 'codigo' => $p->codigo, 'nombre' => $p->nombre,
        'stock' => (int) $p->stock, 'precio' => (float) $p->precio_venta,
    ]]);
    $tipoDoc = old('tipo_identificacion', $esVenta ? 'TICKET' : 'FACTURA');
@endphp

<main class="main">
    <div class="container-fluid">

        <div class="page-head">
            <div>
                <a href="{{ url($cfg['url']) }}" class="back-link"><i class="fa fa-arrow-left"></i> {{ $cfg['listado'] }}</a>
                <h1>{{ $cfg['titulo'] }}</h1>
                <p>{{ $cfg['ayuda'] }}</p>
            </div>
        </div>

        <form id="form-comprobante" action="{{ $cfg['ruta'] }}" method="POST" autocomplete="off" novalidate>
            {{ csrf_field() }}
            <input type="hidden" name="total_pagar" id="total_pagar" value="0">

            <div class="row">
                <div class="col-lg-8">

                    {{-- 1. Datos del comprobante --}}
                    <div class="card">
                        <div class="card-header"><h4><span class="step">1</span> Datos del comprobante</h4></div>
                        <div class="card-body no-scroll">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="entidad">{{ $cfg['entidad'] }} <span class="req">*</span></label>
                                    <select class="form-control" name="{{ $cfg['campoEntidad'] }}" id="entidad" required>
                                        <option value="" disabled {{ old($cfg['campoEntidad']) ? '' : 'selected' }}>Seleccioná un {{ strtolower($cfg['entidad']) }}</option>
                                        @foreach ($entidades as $e)
                                            <option value="{{ $e->id }}" {{ old($cfg['campoEntidad']) == $e->id ? 'selected' : '' }}>
                                                {{ $e->nombre }}{{ $e->num_documento ? ' · '.$e->num_documento : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Comprobante <span class="req">*</span></label>
                                    <div class="segmented" role="radiogroup">
                                        <label><input type="radio" name="tipo_identificacion" value="FACTURA" {{ $tipoDoc === 'FACTURA' ? 'checked' : '' }}><span><i class="fa fa-file-text-o"></i> Factura</span></label>
                                        <label><input type="radio" name="tipo_identificacion" value="TICKET" {{ $tipoDoc === 'TICKET' ? 'checked' : '' }}><span><i class="fa fa-ticket"></i> Ticket</span></label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="numero">Número <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="numero" name="{{ $cfg['campoNumero'] }}"
                                           value="{{ old($cfg['campoNumero'], $siguiente) }}" maxlength="10" required>
                                    <small class="field-hint">Máximo 10 caracteres</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Productos --}}
                    <div class="card">
                        <div class="card-header">
                            <h4><span class="step">2</span> Productos</h4>
                            <small style="color: var(--c-muted)">IVA {{ 20 }} % incluido en el total</small>
                        </div>
                        <div class="card-body no-scroll">
                            <div class="add-bar {{ $esVenta ? 'con-descuento' : '' }}">
                                <div class="form-group">
                                    <label for="producto">Producto</label>
                                    <select class="form-control" id="producto">
                                        <option value="">Buscá o seleccioná un producto…</option>
                                        @foreach ($productos as $p)
                                            <option value="{{ $p->id }}">{{ $p->nombre }} — {{ $p->codigo }}</option>
                                        @endforeach
                                    </select>
                                    <small class="field-hint" id="producto-info">&nbsp;</small>
                                </div>
                                <div class="form-group">
                                    <label for="cantidad">Cantidad</label>
                                    <input type="number" class="form-control" id="cantidad" min="1" step="1" value="1">
                                </div>
                                <div class="form-group">
                                    <label for="precio">{{ $esVenta ? 'Precio' : 'Costo unitario' }}</label>
                                    <div class="input-prefix" data-prefix="Gs">
                                        <input type="number" class="form-control" id="precio" min="0" step="50" {{ $esVenta ? 'readonly' : '' }} placeholder="0">
                                    </div>
                                </div>
                                @if ($esVenta)
                                    <div class="form-group">
                                        <label for="descuento">Desc.</label>
                                        <div class="input-suffix" data-suffix="%">
                                            <input type="number" class="form-control" id="descuento" min="0" max="99" step="1" value="0">
                                        </div>
                                    </div>
                                @endif
                                <button type="button" class="btn btn-primary" id="agregar"><i class="fa fa-plus"></i> Agregar</button>
                            </div>

                            <div class="items-wrap">
                            <table class="table items-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="num">{{ $esVenta ? 'Precio' : 'Costo' }}</th>
                                        <th class="num">Cantidad</th>
                                        <th class="num">Subtotal</th>
                                        <th style="width: 50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="items"></tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Resumen --}}
                <div class="col-lg-4">
                    <div class="card summary">
                        <div class="card-header"><h4><span class="step">3</span> Resumen</h4></div>
                        <div class="card-body">
                            <div class="summary-row"><span>Subtotal</span><span id="r-subtotal">Gs 0</span></div>
                            @if ($esVenta)
                                <div class="summary-row"><span>Descuentos</span><span id="r-descuento">Gs 0</span></div>
                            @endif
                            <div class="summary-row"><span>IVA (20 %)</span><span id="r-iva">Gs 0</span></div>
                            <div class="summary-row summary-total"><span>Total</span><span id="r-total">Gs 0</span></div>

                            <div class="summary-meta">
                                <span>Productos: <strong id="r-items">0</strong></span>
                                <span>Unidades: <strong id="r-unidades">0</strong></span>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block" id="registrar" disabled>
                                <i class="fa fa-check"></i> Registrar {{ $tipo }}
                            </button>
                            <a href="{{ url($cfg['url']) }}" class="cancel">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</main>

@push('scripts')
<script>
(function () {
    var ES_VENTA = @json($esVenta);
    var IVA = 0.20;
    var CAMPO_PRECIO = @json($cfg['campoPrecio']);
    var catalogo = @json($catalogo);
    var items = [];   // { id, cantidad, precio, descuento }

    var $ = window.jQuery;
    var fmt = function (n) { return 'Gs ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var esc = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };
    var alerta = function (msg) {
        var A = window.Swal || window.Sweetalert2;
        if (A && A.fire) { A.fire({ type: 'warning', icon: 'warning', text: msg, confirmButtonColor: '#0f766e' }); }
        else { alert(msg); }
    };

    // Unidades de un producto ya cargadas en el detalle (excepto la línea indicada)
    function enDetalle(id, excepto) {
        return items.reduce(function (t, it, i) { return t + (it.id === id && i !== excepto ? it.cantidad : 0); }, 0);
    }
    function subtotalLinea(it) { return it.cantidad * it.precio * (1 - (it.descuento || 0) / 100); }

    function mostrarInfo() {
        var p = catalogo[$('#producto').val()];
        if (!p) { $('#producto-info').html('&nbsp;'); if (ES_VENTA) { $('#precio').val(''); } return; }
        var disponible = p.stock - enDetalle(p.id);
        $('#producto-info').html(
            'Stock ' + (ES_VENTA ? 'disponible' : 'actual') + ': <strong>' + disponible + '</strong> · Precio de venta: <strong>' + fmt(p.precio) + '</strong>'
        );
        if (ES_VENTA) { $('#precio').val(p.precio); }
        $('#cantidad').attr('max', ES_VENTA ? Math.max(disponible, 0) : null);
    }

    function agregar() {
        var p = catalogo[$('#producto').val()];
        var cantidad = parseInt($('#cantidad').val(), 10);
        var precio = parseFloat($('#precio').val());
        var descuento = ES_VENTA ? parseFloat($('#descuento').val() || 0) : 0;

        if (!p) { return alerta('Seleccioná un producto.'); }
        if (!(cantidad > 0)) { return alerta('Ingresá una cantidad mayor a 0.'); }
        if (!(precio >= 0) || isNaN(precio)) { return alerta('Ingresá el costo unitario.'); }
        if (descuento < 0 || descuento >= 100) { return alerta('El descuento debe estar entre 0 y 99 %.'); }
        if (ES_VENTA && enDetalle(p.id) + cantidad > p.stock) {
            return alerta('No hay stock suficiente de «' + p.nombre + '». Disponible: ' + (p.stock - enDetalle(p.id)) + '.');
        }

        // Si ya existe la misma línea (mismo producto, precio y descuento) se suman las cantidades
        var existente = items.filter(function (it) { return it.id === p.id && it.precio === precio && it.descuento === descuento; })[0];
        if (existente) { existente.cantidad += cantidad; }
        else { items.push({ id: p.id, cantidad: cantidad, precio: precio, descuento: descuento }); }

        $('#producto').val('').trigger('focus');
        $('#cantidad').val(1);
        if (ES_VENTA) { $('#descuento').val(0); } else { $('#precio').val(''); }
        render();
    }

    function render() {
        var $tb = $('#items').empty();
        var bruto = 0, neto = 0, unidades = 0;

        if (!items.length) {
            $tb.append('<tr class="items-empty"><td colspan="5"><i class="fa fa-inbox"></i>Todavía no agregaste productos.</td></tr>');
        }

        items.forEach(function (it, i) {
            var p = catalogo[it.id] || { nombre: 'Producto #' + it.id, codigo: '', stock: 0 };
            var sub = subtotalLinea(it);
            bruto += it.cantidad * it.precio; neto += sub; unidades += it.cantidad;

            $tb.append(
                '<tr>' +
                  '<td class="item-name"><strong>' + esc(p.nombre) + '</strong><small>' + esc(p.codigo) +
                      (it.descuento ? '<span class="badge-off">−' + it.descuento + ' %</span>' : '') + '</small>' +
                      '<input type="hidden" name="id_producto[]" value="' + it.id + '">' +
                      '<input type="hidden" name="' + CAMPO_PRECIO + '[]" value="' + it.precio + '">' +
                      (ES_VENTA ? '<input type="hidden" name="descuento[]" value="' + it.descuento + '">' : '') +
                  '</td>' +
                  '<td class="num">' + fmt(it.precio) + '</td>' +
                  '<td class="num"><input type="number" class="form-control qty-input" name="cantidad[]" min="1" step="1" value="' + it.cantidad + '" data-i="' + i + '"' +
                      (ES_VENTA ? ' max="' + (p.stock - enDetalle(it.id, i)) + '"' : '') + '></td>' +
                  '<td class="num"><strong>' + fmt(sub) + '</strong></td>' +
                  '<td class="num"><button type="button" class="btn btn-danger btn-icon" data-quitar="' + i + '" title="Quitar"><i class="fa fa-trash-o"></i></button></td>' +
                '</tr>'
            );
        });

        var iva = neto * IVA, total = neto + iva;
        $('#r-subtotal').text(fmt(bruto));
        $('#r-descuento').text(bruto - neto > 0 ? '− ' + fmt(bruto - neto) : fmt(0));
        $('#r-iva').text(fmt(iva));
        $('#r-total').text(fmt(total));
        $('#r-items').text(items.length);
        $('#r-unidades').text(unidades);
        $('#total_pagar').val(total.toFixed(2));
        $('#registrar').prop('disabled', !items.length);
        mostrarInfo();
    }

    // Eventos
    $('#producto').on('change', mostrarInfo);
    $('#agregar').on('click', agregar);
    $('#cantidad, #precio, #descuento').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); agregar(); } });

    $('#items').on('click', '[data-quitar]', function () {
        items.splice(+$(this).data('quitar'), 1);
        render();
    }).on('change', '.qty-input', function () {
        var i = +$(this).data('i'), it = items[i], valor = parseInt(this.value, 10);
        var max = ES_VENTA ? catalogo[it.id].stock - enDetalle(it.id, i) : Infinity;
        if (!(valor > 0)) { valor = 1; }
        if (valor > max) { alerta('Solo hay ' + max + ' unidades disponibles de «' + catalogo[it.id].nombre + '».'); valor = max; }
        it.cantidad = valor;
        render();
    });

    $('#form-comprobante').on('submit', function (e) {
        if (!$('#entidad').val()) { e.preventDefault(); return alerta(@json('Seleccioná un '.strtolower($cfg['entidad']).'.')); }
        if (!$.trim($('#numero').val())) { e.preventDefault(); return alerta('Ingresá el número de comprobante.'); }
        if (!items.length) { e.preventDefault(); return alerta('Agregá al menos un producto.'); }
        $('#registrar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando…');
    });

    // Restaurar el detalle si la validación del servidor falló
    @json($itemsPrevios).forEach(function (it) { if (catalogo[it.id]) { items.push(it); } });
    render();
})();
</script>
@endpush

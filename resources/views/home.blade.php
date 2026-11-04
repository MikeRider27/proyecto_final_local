@extends('principal')
@section('contenido')
@php
    $mesesEs = [
        'January' => 'Ene', 'February' => 'Feb', 'March' => 'Mar', 'April' => 'Abr',
        'May' => 'May', 'June' => 'Jun', 'July' => 'Jul', 'August' => 'Ago',
        'September' => 'Sep', 'October' => 'Oct', 'November' => 'Nov', 'December' => 'Dic',
    ];
    // Las consultas vienen ordenadas del mes más reciente al más antiguo: se invierten para el gráfico.
    $serie = fn ($filas) => [
        'labels' => collect($filas)->reverse()->map(fn ($r) => $mesesEs[$r->mes] ?? $r->mes)->values(),
        'data'   => collect($filas)->reverse()->map(fn ($r) => (float) $r->totalmes)->values(),
    ];
    $compras = $serie($comprasmes);
    $ventas  = $serie($ventasmes);

    $total       = $totales[0] ?? (object) ['totalcompra' => 0, 'totalventa' => 0];
    $balance     = $total->totalventa - $total->totalcompra;
    $gs          = fn ($n) => 'Gs ' . number_format($n, 0, ',', '.');
    $maxVendido  = collect($productosvendidos)->max('cantidad') ?: 1;
    $nombre      = Auth::user()->nombre ?: Auth::user()->usuario;
@endphp

<main class="main">
    <div class="container-fluid">

        <div class="dash-hero">
            <div>
                <h1>Hola, {{ explode(' ', trim($nombre))[0] }} 👋</h1>
                <p>Este es el resumen de la operación de hoy.</p>
            </div>
            <span class="date-chip">
                <i class="fa fa-calendar-o"></i>
                {{ ucfirst(now()->locale('es')->translatedFormat('l j \d\e F, Y')) }}
            </span>
        </div>

        <!-- Indicadores del día -->
        <div class="row">
            <div class="col-md-4">
                <div class="kpi kpi--compra">
                    <span class="kpi-icon"><i class="fa fa-shopping-cart"></i></span>
                    <span class="kpi-label">Compras de hoy</span>
                    <span class="kpi-value">{{ $gs($total->totalcompra) }}</span>
                    @if (in_array(Auth::user()->idrol, [1, 3]))
                        <a href="{{ url('compra') }}" class="kpi-link">Ver compras <i class="fa fa-arrow-right"></i></a>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi kpi--venta">
                    <span class="kpi-icon"><i class="fa fa-shopping-bag"></i></span>
                    <span class="kpi-label">Ventas de hoy</span>
                    <span class="kpi-value">{{ $gs($total->totalventa) }}</span>
                    @if (in_array(Auth::user()->idrol, [1, 2]))
                        <a href="{{ url('venta') }}" class="kpi-link">Ver ventas <i class="fa fa-arrow-right"></i></a>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi kpi--neto">
                    <span class="kpi-icon"><i class="fa fa-balance-scale"></i></span>
                    <span class="kpi-label">Balance del día</span>
                    <span class="kpi-value">{{ $balance < 0 ? '− ' : '' }}{{ $gs(abs($balance)) }}</span>
                    <span class="kpi-link" style="color: var(--c-muted)">Ventas − compras</span>
                </div>
            </div>
        </div>

        <!-- Gráficos mensuales -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card chart-card">
                    <div class="card-header">
                        <h4>Compras por mes</h4>
                        <small>Últimos 12 meses</small>
                    </div>
                    <div class="card-body">
                        @if (count($comprasmes))
                            <div class="chart-box"><canvas id="compras"></canvas></div>
                        @else
                            <div class="chart-empty"><div><i class="fa fa-line-chart"></i>Todavía no hay compras registradas.</div></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card chart-card">
                    <div class="card-header">
                        <h4>Ventas por mes</h4>
                        <small>Últimos 12 meses</small>
                    </div>
                    <div class="card-body">
                        @if (count($ventasmes))
                            <div class="chart-box"><canvas id="ventas"></canvas></div>
                        @else
                            <div class="chart-empty"><div><i class="fa fa-bar-chart"></i>Todavía no hay ventas registradas.</div></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos más vendidos -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fa fa-trophy" style="color: var(--c-accent)"></i>&nbsp; Productos más vendidos</h4>
                <small style="color: var(--c-muted)">Año {{ date('Y') }} · unidades</small>
            </div>
            <div class="card-body">
                @if (count($productosvendidos))
                    <ol class="rank-list">
                        @foreach ($productosvendidos as $i => $p)
                            <li>
                                <span class="rank-pos">{{ $i + 1 }}</span>
                                <span class="rank-name">{{ $p->producto }}</span>
                                <span class="rank-bar"><span style="width: {{ round($p->cantidad / $maxVendido * 100) }}%"></span></span>
                                <span class="rank-qty">{{ number_format($p->cantidad, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="chart-empty" style="height: 140px"><div><i class="fa fa-trophy"></i>Aún no hay ventas este año.</div></div>
                @endif
            </div>
        </div>

    </div>
</main>

@push('scripts')
<script>
$(function () {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.global.defaultFontFamily = getComputedStyle(document.body).fontFamily;
    Chart.defaults.global.defaultFontColor = '#64748b';

    var gs = function (v) { return 'Gs ' + Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var opciones = {
        maintainAspectRatio: false,
        legend: { display: false },
        tooltips: {
            backgroundColor: '#0f172a', xPadding: 12, yPadding: 10, cornerRadius: 8, displayColors: false,
            callbacks: { label: function (t) { return gs(t.yLabel); } }
        },
        scales: {
            xAxes: [{ gridLines: { display: false } }],
            yAxes: [{
                ticks: { beginAtZero: true, maxTicksLimit: 5, callback: gs },
                gridLines: { color: '#eef2f6', zeroLineColor: '#e2e8f0', drawBorder: false }
            }]
        }
    };

    var elCompras = document.getElementById('compras');
    if (elCompras) {
        var ctx = elCompras.getContext('2d');
        var grad = ctx.createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(37, 99, 235, .25)');
        grad.addColorStop(1, 'rgba(37, 99, 235, 0)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($compras['labels']),
                datasets: [{
                    label: 'Compras', data: @json($compras['data']),
                    borderColor: '#2563eb', backgroundColor: grad, borderWidth: 2.5,
                    pointBackgroundColor: '#fff', pointBorderColor: '#2563eb', pointBorderWidth: 2, pointRadius: 4,
                    lineTension: .35
                }]
            },
            options: opciones
        });
    }

    var elVentas = document.getElementById('ventas');
    if (elVentas) {
        new Chart(elVentas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($ventas['labels']),
                datasets: [{
                    label: 'Ventas', data: @json($ventas['data']),
                    backgroundColor: '#0d9488', hoverBackgroundColor: '#0f766e', borderWidth: 0
                }]
            },
            options: $.extend(true, {}, opciones, { scales: { xAxes: [{ barPercentage: .55, gridLines: { display: false } }] } })
        });
    }
});
</script>
@endpush

@endsection

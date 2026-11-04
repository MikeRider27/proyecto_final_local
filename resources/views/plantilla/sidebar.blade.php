@php
    // Roles: 1 = Administrador, 2 = Vendedor, 3 = Comprador
    $rol = Auth::user()->idrol;

    $secciones = [
        'Inventario' => [
            ['url' => 'categoria', 'icono' => 'fa-tags',          'texto' => 'Categorías',  'roles' => [1, 2, 3]],
            ['url' => 'producto',  'icono' => 'fa-cube',          'texto' => 'Productos',   'roles' => [1, 2, 3]],
        ],
        'Compras' => [
            ['url' => 'compra',    'icono' => 'fa-shopping-cart', 'texto' => 'Compras',     'roles' => [1, 3]],
            ['url' => 'proveedor', 'icono' => 'fa-truck',         'texto' => 'Proveedores', 'roles' => [1, 3]],
        ],
        'Ventas' => [
            ['url' => 'venta',     'icono' => 'fa-shopping-bag',  'texto' => 'Ventas',      'roles' => [1, 2]],
            ['url' => 'cliente',   'icono' => 'fa-address-book',  'texto' => 'Clientes',    'roles' => [1, 2]],
        ],
        'Administración' => [
            ['url' => 'user',      'icono' => 'fa-user-circle',   'texto' => 'Usuarios',    'roles' => [1]],
            ['url' => 'rol',       'icono' => 'fa-shield',        'texto' => 'Roles',       'roles' => [1]],
        ],
    ];
@endphp

<div class="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('home') || request()->is('/') ? 'active' : '' }}" href="{{ url('home') }}">
                    <i class="fa fa-th-large"></i> Panel
                </a>
            </li>

            @foreach ($secciones as $titulo => $items)
                @php $visibles = array_filter($items, fn ($i) => in_array($rol, $i['roles'])); @endphp
                @if (count($visibles))
                    <li class="nav-title">{{ $titulo }}</li>
                    @foreach ($visibles as $item)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is($item['url'].'*') ? 'active' : '' }}" href="{{ url($item['url']) }}">
                                <i class="fa {{ $item['icono'] }}"></i> {{ $item['texto'] }}
                            </a>
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>
    </nav>
    <button class="sidebar-minimizer brand-minimizer" type="button"></button>
</div>

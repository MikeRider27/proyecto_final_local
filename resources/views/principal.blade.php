<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistema Compras-Ventas con Laravel y Vue Js- mike.mavc27@gmail.com">
    <meta name="keyword" content="Sistema Compras-Ventas con Laravel y Vue Js">
@php
    $titulos = [
        'home' => 'Panel', 'categoria' => 'Categorías', 'producto' => 'Productos',
        'compra' => 'Compras', 'proveedor' => 'Proveedores', 'venta' => 'Ventas',
        'cliente' => 'Clientes', 'user' => 'Usuarios', 'rol' => 'Roles',
    ];
    $tituloPagina = $titulos[request()->segment(1) ?? 'home'] ?? 'Panel';
    $roles = [1 => 'Administrador', 2 => 'Vendedor', 3 => 'Comprador'];
    $usuarioActual = Auth::user();
    $iniciales = collect(explode(' ', trim($usuarioActual->nombre ?: $usuarioActual->usuario)))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $tieneImagen = $usuarioActual->imagen && file_exists(public_path('storage/img/usuario/'.$usuarioActual->imagen));
@endphp
    <title>{{ $tituloPagina }} · Compras & Ventas</title>
    <link rel="icon" href="{{asset('img/favicon.png')}}">
    <!-- Tipografía -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="{{asset('css/font-awesome.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/simple-line-icons.min.css')}}" rel="stylesheet">
    <!-- Plantilla base + tema propio -->
    <link href="{{asset('css/style.css')}}" rel="stylesheet">
    <link href="{{asset('css/tema.css')}}" rel="stylesheet">
</head>

<body class="app header-fixed sidebar-fixed aside-menu-fixed aside-menu-hidden">
<header class="app-header navbar">
        <button class="navbar-toggler mobile-sidebar-toggler d-lg-none mr-auto" type="button" aria-label="Menú">
          <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="{{url('home')}}">
            <span class="brand-logo"><i class="fa fa-cube"></i></span>
            <span class="brand-text">Compras & Ventas<small>Gestión comercial</small></span>
        </a>
        <button class="navbar-toggler sidebar-toggler d-md-down-none" type="button" aria-label="Ocultar menú">
          <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="page-heading d-md-down-none">{{ $tituloPagina }}</h1>

        <ul class="nav navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle user-chip" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    @if ($tieneImagen)
                        <img src="{{asset('storage/img/usuario/'.$usuarioActual->imagen)}}" class="avatar" alt="{{$usuarioActual->usuario}}">
                    @else
                        <span class="avatar">{{ $iniciales }}</span>
                    @endif
                    <span class="user-meta d-md-down-none">
                        <strong>{{ $usuarioActual->nombre ?: $usuarioActual->usuario }}</strong>
                        <small>{{ $roles[$usuarioActual->idrol] ?? 'Usuario' }}</small>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="dropdown-header">{{ '@'.$usuarioActual->usuario }}</div>
                    <a class="dropdown-item" href="{{route('logout')}}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa fa-sign-out"></i> Cerrar sesión</a>

                    <form id="logout-form" action="{{route('logout')}}" method="POST" style="display: none;">
                    {{ csrf_field() }}
                    </form>
                </div>
            </li>
        </ul>
    </header>

    <div class="app-body">

        @include('plantilla.sidebar')

        <!-- Contenido Principal -->

           @yield('contenido')

        <!-- /Fin del contenido principal -->
    </div>

    <footer class="app-footer">
        <span>&copy; {{ date('Y') }} Compras & Ventas</span>
        <span class="ml-auto">Desarrollado por <a href="mailto:mike.mavc27@gmail.com">Miguel Villalba</a></span>
    </footer>

    <!-- Bootstrap and necessary plugins -->
    <script src="{{asset('js/jquery.min.js')}}"></script>
    @stack('scripts')
    <script src="{{asset('js/popper.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/pace.min.js')}}"></script>
    <!-- Plugins and scripts required by all views -->
    <script src="{{asset('js/Chart.min.js')}}"></script>
    <!-- GenesisUI main scripts -->
    <script src="{{asset('js/template.js')}}"></script>
    <script src="{{asset('js/sweetalert2.all.min.js')}}"></script>

    {{-- Avisos de resultado (éxito, error y validación) --}}
    @php
        $aviso = session('success')
            ? ['type' => 'success', 'title' => session('success')]
            : (session('error') || $errors->any()
                ? ['type' => 'error', 'title' => session('error') ?: $errors->first()]
                : null);
    @endphp
    @if ($aviso)
    <script>
        (function (aviso) {
            var Alerta = window.Swal || window.Sweetalert2 || window.swal;
            if (!Alerta) { return; }
            Alerta.fire ? Alerta.fire({
                toast: true, position: 'top-end', type: aviso.type, icon: aviso.type, title: aviso.title,
                showConfirmButton: false, timer: 4500
            }) : Alerta({ toast: true, position: 'top-end', type: aviso.type, title: aviso.title, showConfirmButton: false, timer: 4500 });
        })(@json($aviso));
    </script>
    @endif

    <script>

         /*EDITAR CATEGORIA EN VENTANA MODAL*/
         $('#abrirmodalEditar').on('show.bs.modal', function (event) {

        //console.log('modal abierto');

        var button = $(event.relatedTarget)
        var nombre_modal_editar = button.data('nombre')
        var descripcion_modal_editar = button.data('descripcion')
        var id_categoria = button.data('id_categoria')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)
        modal.find('.modal-body #nombre').val(nombre_modal_editar);
        modal.find('.modal-body #descripcion').val(descripcion_modal_editar);
        modal.find('.modal-body #id_categoria').val(id_categoria);
        })


         /******************************************************/
        /*INICIO ventana modal para cambiar estado de Categoria*/

        $('#cambiarEstado').on('show.bs.modal', function (event) {

        //console.log('modal abierto');

        var button = $(event.relatedTarget)
        var id_categoria = button.data('id_categoria')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)

        modal.find('.modal-body #id_categoria').val(id_categoria);
        })

        /*FIN ventana modal para cambiar estado de la categoria*/

         /*EDITAR PRODUCTO EN VENTANA MODAL*/
         $('#abrirmodalEditar').on('show.bs.modal', function (event) {

        //console.log('modal abierto');
        /*el button.data es lo que está en el button de editar*/
        var button = $(event.relatedTarget)
        /*este id_categoria_modal_editar selecciona la categoria*/
        var id_categoria_modal_editar = button.data('id_categoria')
        var nombre_modal_editar = button.data('nombre')
        var precio_venta_modal_editar = button.data('precio_venta')
        var codigo_modal_editar = button.data('codigo')
        var stock_modal_editar = button.data('stock')
        //var imagen_modal_editar = button.data('imagen1')
        var id_producto = button.data('id_producto')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)
        /*los # son los id que se encuentran en el formulario*/
        modal.find('.modal-body #id').val(id_categoria_modal_editar);
        modal.find('.modal-body #nombre').val(nombre_modal_editar);
        modal.find('.modal-body #precio_venta').val(precio_venta_modal_editar);
        modal.find('.modal-body #codigo').val(codigo_modal_editar);
        modal.find('.modal-body #stock').val(stock_modal_editar);
       // modal.find('.modal-body #subirImagen').html("<img src="img/producto/imagen_modal_editar">");
        modal.find('.modal-body #id_producto').val(id_producto);
        })

        /*INICIO ventana modal para cambiar el estado del producto*/

        $('#cambiarEstado').on('show.bs.modal', function (event) {

        //console.log('modal abierto');

        var button = $(event.relatedTarget)
        var id_producto = button.data('id_producto')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)

        modal.find('.modal-body #id_producto').val(id_producto);
        })

        /*FIN ventana modal para cambiar estado del producto*/

         /*EDITAR PROVEEDOR EN VENTANA MODAL*/
         $('#abrirmodalEditar').on('show.bs.modal', function (event) {

        //console.log('modal abierto');
        /*el button.data es lo que está en el button de editar*/
        var button = $(event.relatedTarget)

        var nombre_modal_editar = button.data('nombre')
        var tipo_documento_modal_editar = button.data('tipo_documento')
        var num_documento_modal_editar = button.data('num_documento')
        var direccion_modal_editar = button.data('direccion')
        var telefono_modal_editar = button.data('telefono')
        var email_modal_editar = button.data('email')
        var id_proveedor = button.data('id_proveedor')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)
        /*los # son los id que se encuentran en el formulario*/
        modal.find('.modal-body #nombre').val(nombre_modal_editar);
        modal.find('.modal-body #tipo_documento').val(tipo_documento_modal_editar);
        modal.find('.modal-body #num_documento').val(num_documento_modal_editar);
        modal.find('.modal-body #direccion').val(direccion_modal_editar);
        modal.find('.modal-body #telefono').val(telefono_modal_editar);
        modal.find('.modal-body #email').val(email_modal_editar);
        modal.find('.modal-body #id_proveedor').val(id_proveedor);
        })

         /*EDITAR CLIENTE EN VENTANA MODAL*/
         $('#abrirmodalEditar').on('show.bs.modal', function (event) {

        //console.log('modal abierto');
        /*el button.data es lo que está en el button de editar*/
        var button = $(event.relatedTarget)

        var nombre_modal_editar = button.data('nombre')
        var tipo_documento_modal_editar = button.data('tipo_documento')
        var num_documento_modal_editar = button.data('num_documento')
        var direccion_modal_editar = button.data('direccion')
        var telefono_modal_editar = button.data('telefono')
        var email_modal_editar = button.data('email')
        var id_cliente = button.data('id_cliente')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)
        /*los # son los id que se encuentran en el formulario*/
        modal.find('.modal-body #nombre').val(nombre_modal_editar);
        modal.find('.modal-body #tipo_documento').val(tipo_documento_modal_editar);
        modal.find('.modal-body #num_documento').val(num_documento_modal_editar);
        modal.find('.modal-body #direccion').val(direccion_modal_editar);
        modal.find('.modal-body #telefono').val(telefono_modal_editar);
        modal.find('.modal-body #email').val(email_modal_editar);
        modal.find('.modal-body #id_cliente').val(id_cliente);
        })


         /*EDITAR USUARIO EN VENTANA MODAL*/
         $('#abrirmodalEditar').on('show.bs.modal', function (event) {

        //console.log('modal abierto');
        /*el button.data es lo que está en el button de editar*/
        var button = $(event.relatedTarget)

        var nombre_modal_editar = button.data('nombre')
        var tipo_documento_modal_editar = button.data('tipo_documento')
        var num_documento_modal_editar = button.data('num_documento')
        var direccion_modal_editar = button.data('direccion')
        var telefono_modal_editar = button.data('telefono')
        var email_modal_editar = button.data('email')
        /*este id_rol_modal_editar selecciona la categoria*/
        var id_rol_modal_editar = button.data('id_rol')
        var usuario_modal_editar = button.data('usuario')
        //var password_modal_editar = button.data('password')
        var id_usuario = button.data('id_usuario')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)
        /*los # son los id que se encuentran en el formulario*/
        modal.find('.modal-body #nombre').val(nombre_modal_editar);
        modal.find('.modal-body #tipo_documento').val(tipo_documento_modal_editar);
        modal.find('.modal-body #num_documento').val(num_documento_modal_editar);
        modal.find('.modal-body #direccion').val(direccion_modal_editar);
        modal.find('.modal-body #telefono').val(telefono_modal_editar);
        modal.find('.modal-body #email').val(email_modal_editar);
        modal.find('.modal-body #id_rol').val(id_rol_modal_editar);
        modal.find('.modal-body #usuario').val(usuario_modal_editar);
        //modal.find('.modal-body #password').val(password_modal_editar);
        modal.find('.modal-body #id_usuario').val(id_usuario);
        })

     /*INICIO ventana modal para cambiar el estado del usuario*/

        $('#cambiarEstado').on('show.bs.modal', function (event) {

        //console.log('modal abierto');

        var button = $(event.relatedTarget)
        var id_usuario = button.data('id_usuario')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)

        modal.find('.modal-body #id_usuario').val(id_usuario);
        })

        /*FIN ventana modal para cambiar estado del usuario*/

         /*INICIO ventana modal para cambiar estado de Compra*/

        $('#cambiarEstadoCompra').on('show.bs.modal', function (event) {

       //console.log('modal abierto');

       var button = $(event.relatedTarget)
       var id_compra = button.data('id_compra')
       var modal = $(this)
       // modal.find('.modal-title').text('New message to ' + recipient)

       modal.find('.modal-body #id_compra').val(id_compra);
       })

       /*FIN ventana modal para cambiar estado de la compra*/

       /*INICIO ventana modal para cambiar estado de Venta*/

       $('#cambiarEstadoVenta').on('show.bs.modal', function (event) {

        //console.log('modal abierto');

        var button = $(event.relatedTarget)
        var id_venta = button.data('id_venta')
        var modal = $(this)
        // modal.find('.modal-title').text('New message to ' + recipient)

        modal.find('.modal-body #id_venta').val(id_venta);
        })

        /*FIN ventana modal para cambiar estado de la venta*/


    </script>


</body>

</html>

@extends('auth.contenido')

@section('login')
<div class="auth-page">

    <aside class="auth-aside">
        <div class="brand">
            <span class="brand-logo"><i class="fa fa-cube"></i></span>
            Compras & Ventas
        </div>

        <div>
            <h1>Tu negocio, <span>bajo control</span>.</h1>
            <p>Gestioná inventario, proveedores, clientes, compras y ventas desde un solo lugar.</p>
            <ul class="auth-features">
                <li><i class="fa fa-cube"></i> Stock de productos actualizado al instante</li>
                <li><i class="fa fa-file-pdf-o"></i> Comprobantes de compra y venta en PDF</li>
                <li><i class="fa fa-line-chart"></i> Estadísticas mensuales de tu operación</li>
            </ul>
        </div>

        <footer>&copy; {{ date('Y') }} · Desarrollado por Miguel Villalba</footer>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <h2>Bienvenido de nuevo</h2>
            <p class="lead">Ingresá tus credenciales para continuar.</p>

            @if ($errors->any())
                <div class="auth-error">
                    <i class="fa fa-exclamation-circle"></i>
                    {{ $errors->first('usuario') ?: $errors->first('password') ?: $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" autocomplete="on">
                {{ csrf_field() }}

                <div class="auth-field">
                    <label for="usuario">Usuario</label>
                    <div class="auth-input">
                        <i class="fa fa-user-o"></i>
                        <input type="text" name="usuario" id="usuario" value="{{ old('usuario') }}"
                               class="form-control" placeholder="Tu nombre de usuario" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password">Contraseña</label>
                    <div class="auth-input">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" id="password"
                               class="form-control" placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" aria-label="Mostrar contraseña"
                                onclick="var p=document.getElementById('password'),s=p.type==='password';p.type=s?'text':'password';this.firstElementChild.className='fa '+(s?'fa-eye-slash':'fa-eye');">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    Iniciar sesión <i class="fa fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </main>

</div>
@endsection

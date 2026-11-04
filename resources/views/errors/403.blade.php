@extends('principal')
@section('contenido')
<main class="main">
    <div class="container-fluid">
        <div class="card" style="max-width: 520px; margin: 3rem auto; text-align: center;">
            <div class="card-body" style="padding: 2.5rem 2rem;">
                <span class="kpi-icon" style="--kpi-soft: var(--c-danger-50); --kpi: var(--c-danger); margin: 0 auto 1rem; width: 56px; height: 56px; font-size: 22px;">
                    <i class="fa fa-lock"></i>
                </span>
                <h2 style="font-size: 1.4rem; margin-bottom: .5rem;">Acceso restringido</h2>
                <p style="color: var(--c-muted); margin-bottom: 1.5rem;">
                    {{ $exception->getMessage() ?: 'No tenés permiso para acceder a esta sección.' }}
                </p>
                <a href="{{ url('home') }}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Volver al panel</a>
            </div>
        </div>
    </div>
</main>
@endsection

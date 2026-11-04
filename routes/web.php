<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Cada ruta se define una sola vez y se protege con el middleware "rol",
| que recibe los roles habilitados (ver App\Http\Middleware\Rol).
|
*/

Route::group(['middleware' => ['guest']], function () {

    Route::get('/', 'Auth\LoginController@showLoginForm');
    Route::post('/login', 'Auth\LoginController@login')->name('login');

});


Route::group(['middleware' => ['auth']], function () {

    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

    Route::get('/home', 'HomeController@index');

    // Inventario: todos los roles
    Route::group(['middleware' => ['rol:Administrador,Vendedor,Comprador']], function () {
        Route::resource('categoria', 'CategoriaController');
        Route::resource('producto', 'ProductoController');
        Route::get('/listarProductoPdf', 'ProductoController@listarPdf')->name('productos_pdf');
    });

    // Compras: administrador y comprador
    Route::group(['middleware' => ['rol:Administrador,Comprador']], function () {
        Route::resource('proveedor', 'ProveedorController');
        Route::resource('compra', 'CompraController');
        Route::get('/pdfCompra/{id}', 'CompraController@pdf')->name('compra_pdf');
    });

    // Ventas: administrador y vendedor
    Route::group(['middleware' => ['rol:Administrador,Vendedor']], function () {
        Route::resource('cliente', 'ClienteController');
        Route::resource('venta', 'VentaController');
        Route::get('/pdfVenta/{id}', 'VentaController@pdf')->name('venta_pdf');
    });

    // Administración: solo administrador
    Route::group(['middleware' => ['rol:Administrador']], function () {
        Route::resource('rol', 'RolController');
        Route::resource('user', 'UserController');
    });

});

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(){
        return view('auth.login');
    }

    public function login(Request $request){

        $this->validateLogin($request);      
 
         if (Auth::attempt(['usuario' => $request->usuario,'password' => $request->password,'condicion'=>1])){
             return redirect('/home');
         }

         return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos, o el usuario está desactivado.'])
         ->withInput(request(['usuario']));
     }

     protected function validateLogin(Request $request){
        $this->validate($request,[
            'usuario' => 'required|string',
            'password' => 'required|string'
        ], [
            'usuario.required' => 'Ingresá tu usuario.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        return redirect('/');
    }

}

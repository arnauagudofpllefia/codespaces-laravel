<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'contrasena' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'contrasena' => \Illuminate\Support\Facades\Hash::make($request->contrasena),
                'rol' => 'usuario',
            ]);

            $token = JWTAuth::fromUser($usuario);

            return response()->json([
                'message' => 'Usuario registrado correctamente.',
                'token' => $token,
                'user' => $usuario,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error registrando usuario.'], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'contrasena' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('email', 'contrasena');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas.'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo crear el token.'], 500);
        }

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Sesión cerrada correctamente.']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Error cerrando sesión.'], 500);
        }
    }
}

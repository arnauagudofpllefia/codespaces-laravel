<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Controla el ciclo de autenticación JWT: registro, login y logout.
 */
class AuthController extends Controller
{
    /**
     * Crea un usuario nuevo y devuelve un token JWT listo para usar.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'contrasena' => 'required|string|min:6',
            'gimnasio_id' => 'nullable|integer|exists:gimnasios,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Se almacena la contraseña hasheada, nunca en texto plano.
            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'contrasena' => \Illuminate\Support\Facades\Hash::make($request->contrasena),
                'rol' => 'usuario',
                'gimnasio_id' => $request->gimnasio_id,
                'gimnasio_cambiado_en' => $request->gimnasio_id ? now() : null,
            ]);

            // Se emite el JWT inmediatamente para evitar un segundo login tras registrarse.
            $token = JWTAuth::fromUser($usuario);

            return response()->json([
                'message' => 'Usuario registrado correctamente.',
                'token' => $token,
                'user' => $usuario->load('gimnasio'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error registrando usuario.'], 500);
        }
    }

    /**
     * Valida credenciales y entrega un JWT para las rutas protegidas.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'contrasena' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->contrasena,
        ];

        try {
            // JWTAuth::attempt retorna false cuando email/contraseña no coinciden.
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

    /**
     * Invalida el token actual para cerrar la sesión del cliente.
     */
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

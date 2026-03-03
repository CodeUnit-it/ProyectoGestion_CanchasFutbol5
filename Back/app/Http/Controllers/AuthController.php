<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

// --- NUEVO: Importar las clases para Mail y Log ---
use App\Mail\Correo_Bienvenida;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Registrar nuevo usuario",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "role"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="telefono", type="string", example="123456789"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *             @OA\Property(property="role", type="string", enum={"usuario", "administrador", "master"}, example="usuario")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuario registrado correctamente"),
     *     @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telefono' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:usuario,administrador,master', // ✅ agregar master
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        Log::info('Datos recibidos en register:', $request->all());


        // Crea el usuario en la DB
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
                'role' => $request->role ?? 'usuario', // si no llega, por defecto 'usuario'


        ]);


        // --- Inicio modificación para notificaciones ---
        // Enviar el correo de bienvenida (Sin cola, asincrono.)
        try {
            Mail::to($user->email)->send(new Correo_Bienvenida($user));
        } catch (\Exception $e) {
            // Si el correo falla, que no falle el registro. Se registra el error del correo y continua normal
            Log::error('Error al enviar correo de bienvenida para user_id ' . $user->id . ': ' . $e->getMessage());
        }
        // --- Fin modificación para notificaciones ---

        //Cambio hecho: Le agregué token al register para que después del registro ya se obtenga el token y no haya que loguearse aparte
        $token = auth('api')->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario registrado correctamente',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Iniciar sesión",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="telefono", type="string", example="123456789"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login exitoso"),
     *     @OA\Response(response=401, description="Credenciales inválidas"),
     *     @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function login(Request $request)
    {
        // Validación de credenciales
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password',);

        // Intentar autenticar con JWT
        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        return response()->json([
        'status' => 'success',
        'message' => 'Login exitoso',
        'user' => [
            'id' => auth('api')->user()->id,
            'name' => auth('api')->user()->name,
            'email' => auth('api')->user()->email,
            'telefono' => auth('api')->user()->telefono, // agregado
            'role' => auth('api')->user()->role,
            'created_at' => auth('api')->user()->created_at->toDateTimeString(), // agregado

        ],
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60
    ], 200);

    }
}

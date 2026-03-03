<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Método getUsuariosRegistrados eliminado aquí para evitar duplicados; usar la versión con anotaciones OpenAPI más abajo.

    // 🔹 Obtener un usuario por ID
    public function show(User $user)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        return response()->json($user->only(['id', 'name', 'email', 'telefono', 'role', 'created_at']));
    }

    // 🔹 Registrar un nuevo usuario (público)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telefono' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|in:usuario',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'password' => Hash::make($validated['password']),
            'role' => 'usuario',
        ]);

        return response()->json(['message' => 'Usuario registrado correctamente', 'user' => $user->only(['id', 'name', 'email', 'telefono'])], 201);
    }


    /**
     * @OA\Put(
     * path="/api/users/{id}",
     * summary="Actualizar un usuario existente (Solo Administrador)",
     * tags={"Usuarios"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string"),
     * @OA\Property(property="email", type="string", format="email"),
     * @OA\Property(property="telefono", type="string"),
     * @OA\Property(property="role", type="string", example="administrador") 
     * )
     * ),
     * @OA\Response(response=200, description="Usuario actualizado correctamente"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Administrador)")
     * )
     */

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (!in_array($authUser->role, ['master', 'administrador']) && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Acceso denegado. No puedes editar otros usuarios.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|required|string|in:usuario,administrador,master'
        ]);

        $data = $validated;

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (!in_array($authUser->role, ['master', 'administrador'])) {
            unset($data['role']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user->only(['id', 'name', 'email', 'telefono', 'role'])
        ], 200);
    }


    /**
     * @OA\Delete(
     * path="/api/users/{id}",
     * summary="Eliminar un usuario (Solo Administrador)",
     * tags={"Usuarios"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Usuario eliminado correctamente"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Administrador)")
     * )
     */
    
    // 🔹 Eliminar un usuario (solo admin/master)
    public function destroy(User $user)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta de administrador a través de esta ruta.'], 403);
        }

        $user->delete();
    \Log::info('Usuario eliminado correctamente ID: ' . $user->id);

        return response()->json([
            'message' => 'Usuario eliminado correctamente',
            'user_id' => $user->id
        ], 200);
    }


    /**
     * @OA\Get(
     * path="/api/usuarios-registrados",
     * summary="Obtener lista de usuarios registrados (Solo Administrador)",
     * tags={"Usuarios"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Lista de usuarios"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Administrador)")
     * )
     */

    public function getUsuariosRegistrados()
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o maestro.'], 403);
        }

        $usuarios = User::select('id', 'name', 'email', 'telefono', 'role', 'created_at')->get();

        return response()->json($usuarios);
    }
        public function actualizarTelefono(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'telefono' => 'required|string|max:20'
        ]);

        $user->telefono = $request->telefono;
        $user->save();

        return response()->json($user);
    }

}



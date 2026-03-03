<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cancha;
use Illuminate\Http\Request;

class CanchaController2 extends Controller
{
    // ⚡ Nuevo controlador API para Canchas
    // 👉 Los métodos index y show son públicos. Los de gestión (store, update, destroy)
    //    están protegidos por TOKEN y requieren un ROL de administrador.

    /**
     * Devuelve todas las canchas en formato JSON (PÚBLICA)
     */

    /**
     * @OA\Get(
     * path="/api/canchas2",
     * summary="Obtener todas las canchas",
     * tags={"Canchas"},
     * @OA\Response(response=200, description="Lista de canchas")
     * )
     */
    public function index()
    {
        return response()->json(Cancha::all());
    }

    /**
     * Devuelve una cancha por id (PÚBLICA)
     */

    /**
     * @OA\Get(
     * path="/api/canchas2/{id}",
     * summary="Obtener una cancha por ID",
     * tags={"Canchas"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Datos de la cancha"),
     * @OA\Response(response=404, description="Cancha no encontrada")
     * )
     */
    public function show($id)
    {
        $cancha = Cancha::find($id);

        if (!$cancha) {
            return response()->json(['message' => 'Cancha no encontrada'], 404);
        }

        return response()->json($cancha);
    }


    /**
     * Crea una cancha nueva (PRIVADA: REQUIERE TOKEN Y ROL ADMIN)
     */

    /**
     * @OA\Post(
     * path="/api/canchas2",
     * summary="Crear una nueva cancha (Solo ADMIN)",
     * tags={"Canchas"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre", "tipo"},
     * @OA\Property(property="nombre", type="string", example="Cancha 1"),
     * @OA\Property(property="tipo", type="string", example="Fútbol 5"),
     * @OA\Property(property="precio_hora", type="number", example=1200),
     * @OA\Property(property="cant_jugadores", type="integer", example=10)
     * )
     * ),
     * @OA\Response(response=201, description="Cancha creada correctamente"),
     * @OA\Response(response=401, description="No autenticado (Falta Token)"),
     * @OA\Response(response=403, description="Acceso denegado (Falta Rol)"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|max:255',
            'precio_hora' => 'numeric|min:0',
            'cant_jugadores' => 'integer|min:2',
        ]);

        $cancha = Cancha::create($validated);

        return response()->json($cancha, 201);
    }

    /**
     * Actualiza una cancha existente (PRIVADA: REQUIERE TOKEN Y ROL ADMIN)
     */

    /**
     * @OA\Put(
     * path="/api/canchas2/{id}",
     * summary="Actualizar una cancha existente (Solo ADMIN)",
     * tags={"Canchas"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="nombre", type="string", example="Cancha 1"),
     * @OA\Property(property="tipo", type="string", example="Fútbol 7"),
     * @OA\Property(property="precio_hora", type="number", example=1500),
     * @OA\Property(property="cant_jugadores", type="integer", example=14)
     * )
     * ),
     * @OA\Response(response=200, description="Cancha actualizada correctamente"),
     * @OA\Response(response=401, description="No autenticado (Falta Token)"),
     * @OA\Response(response=403, description="Acceso denegado (Falta Rol)"),
     * @OA\Response(response=404, description="Cancha no encontrada"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, $id)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $cancha = Cancha::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'string|max:255',
            'tipo' => 'string|max:255',
            'precio_hora' => 'numeric|min:0',
            'cant_jugadores' => 'integer|min:2',
        ]);

        if (empty($validated)) {
            return response()->json(['message' => 'No hay datos para actualizar.'], 422);
        }

        $cancha->update($validated);

        return response()->json([
            'message' => 'Cancha actualizada correctamente',
            'cancha' => $cancha
        ]);
    }

    /**
     * Elimina una cancha (PRIVADA: REQUIERE TOKEN Y ROL ADMIN)
     */

    /**
     * @OA\Delete(
     * path="/api/canchas2/{id}",
     * summary="Eliminar una cancha (Solo ADMIN)",
     * tags={"Canchas"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Cancha eliminada correctamente"),
     * @OA\Response(response=401, description="No autenticado (Falta Token)"),
     * @OA\Response(response=403, description="Acceso denegado (Falta Rol)"),
     * @OA\Response(response=404, description="Cancha no encontrada")
     * )
     */
    public function destroy($id)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $cancha = Cancha::findOrFail($id);
        $cancha->delete();

        return response()->json(['message' => 'Cancha eliminada correctamente']);
    }
}

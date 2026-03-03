<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cancha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Mail\Correo_Ticket_Reserva;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ReservaController extends Controller
{
    /**
     * Listar todas las reservas (Solo ADMIN)
     *
     * @OA\Get(
     * path="/api/reservas",
     * summary="Listar todas las reservas (Administrador)",
     * security={{"bearerAuth":{}}},
     * tags={"Reservas"},
     * @OA\Parameter(name="user_id", in="query", @OA\Schema(type="integer")),
     * @OA\Parameter(name="estado", in="query", @OA\Schema(type="string")),
     * @OA\Response(response=200, description="Lista de reservas"),
     * @OA\Response(response=401, description="Usuario no autenticado"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Admin)")
     * )
     */
    public function index(Request $request)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $query = Reserva::with('cancha');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->get());
    }


    /**
     * @OA\Post(
     *   path="/api/reservas",
     *   summary="Crear una nueva reserva",
     *   tags={"Reservas"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"cliente","telefono","fecha","hora_inicio","hora_fin","cancha_id"},
     *       @OA\Property(property="cliente", type="string", example="usuario"),
     *       @OA\Property(property="telefono", type="string", example="1544327689"),
     *       @OA\Property(property="fecha", type="string", format="date"),
     *       @OA\Property(property="hora_inicio", type="string", example="16:00"),
     *       @OA\Property(property="hora_fin", type="string", example="17:00"),
     *       @OA\Property(property="cancha_id", type="integer", example=1),
     *       @OA\Property(property="estado", type="string", example="pendiente")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Reserva creada correctamente"),
     *   @OA\Response(response=401, description="Usuario no autenticado"),
     *   @OA\Response(response=409, description="Conflicto de horario"),
     * @OA\Response(
     *   response=422,
     *   description="Error de validación",
     *   @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(
     *       property="errors",
     *       type="object",
     *       @OA\Property(
     *         property="hora_inicio",
     *         type="array",
     *         @OA\Items(type="string")
     *       ),
     *       @OA\Property(
     *         property="hora_fin",
     *         type="array",
     *         @OA\Items(type="string")
     *       )
     *     )
     *   )
     * )
     * )
     */

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'error' => 'No se detectó usuario autenticado',
                'token' => $request->header('Authorization')
            ], 401);
        }

        try {
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'telefono' => 'required|string|max:20',
                'fecha' => 'required|date',
                'hora_inicio' => [
                    'required',
                    'date_format:H:i',
                    function ($attr, $value, $fail) {
                        $inicio = Carbon::parse($value);
                        $horaMin = Carbon::createFromTime(16, 0);

                        if ($inicio->lt($horaMin)) {
                            $fail('La hora de inicio debe ser a partir de las 16:00.');
                        }
                    }
                ],

                'hora_fin' => [
                    'required',
                    'date_format:H:i',
                    function ($attr, $value, $fail) use ($request) {
                        $inicio = Carbon::parse($request->hora_inicio);
                        $fin = Carbon::parse($value);

                        $horaMin = Carbon::createFromTime(16, 0);
                        $horaMax = Carbon::createFromTime(0, 0)->addDay();

                        if (!($request->hora_inicio == '23:00' && $value == '00:00')) {
                            if ($inicio->lt($horaMin) || $fin->gt($horaMax)) {
                                $fail('Solo se permiten reservas entre las 16:00 y las 00:00.');
                            }
                            if ($fin <= $inicio) {
                                $fail('La hora de fin debe ser posterior a la hora de inicio.');
                            }
                        }
                    }
                ],
                'cancha_id' => 'required|integer|exists:canchas,id',
                'cliente_id' => 'nullable|integer|exists:users,id',

            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }

        // Ajustar hora fin si cruza medianoche
        $horaInicio = Carbon::parse($request->hora_inicio);
        $horaFin = Carbon::parse($request->hora_fin);
        if ($horaFin <= $horaInicio) {
            $horaFin->addDay();
        }

        // Verificar conflictos
        $conflicto = Reserva::where('fecha', $request->fecha)
            ->where('cancha_id', $request->cancha_id)
            ->whereIn('estado', ['pendiente', 'aprobada', 'activa'])
            ->get()
            ->filter(function ($reserva) use ($horaInicio, $horaFin) {
                $resInicio = Carbon::parse($reserva->hora_inicio);
                $resFin = Carbon::parse($reserva->hora_fin);
                if ($resFin <= $resInicio) $resFin->addDay();
                return $horaInicio < $resFin && $horaFin > $resInicio;
            })
            ->isNotEmpty();

        if ($conflicto) {
            return response()->json(['error' => 'Ya existe una reserva en ese horario'], 409);
        }

        $estado = in_array($user->role, ['master', 'administrador']) && $request->filled('estado')
            ? $request->estado
            : 'pendiente';
        $asociadoUserId = $request->filled('cliente_id') ? $request->cliente_id : $user->id;

        $reserva = Reserva::create(array_merge($validated, [
            'user_id' => $asociadoUserId,
            'estado' => $estado,
        ]));


        return response()->json([
            'message' => 'Reserva creada correctamente.',
            'reserva' => $reserva
        ], 201);
    }

    /**
     * @OA\Put(
     *   path="/api/reservas/{reserva}",
     *   summary="Actualizar una reserva (Solo ADMIN)",
     *   tags={"Reservas"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="reserva",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="cliente", type="string", example="usuario"),
     *       @OA\Property(property="telefono", type="string", example="1544327689"),
     *       @OA\Property(property="fecha", type="string", format="date"),
     *       @OA\Property(property="hora_inicio", type="string", example="16:00"),
     *       @OA\Property(property="hora_fin", type="string", example="17:00"),
     *       @OA\Property(property="cancha_id", type="integer",example=1),
     *       @OA\Property(property="estado", type="string", example="pendiente")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Reserva actualizada correctamente"),
     *   @OA\Response(response=403, description="Acceso denegado. Solo administradores"),
     *   @OA\Response(response=404, description="Reserva no encontrada"),
     * @OA\Response(
     *   response=422,
     *   description="Error de validación",
     *   @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(
     *       property="errors",
     *       type="object",
     *       @OA\Property(
     *         property="hora_inicio",
     *         type="array",
     *         @OA\Items(type="string")
     *       ),
     *       @OA\Property(
     *         property="hora_fin",
     *         type="array",
     *         @OA\Items(type="string")
     *       )
     *     )
     *   )
     * )
     * )
     */

    public function update(Request $request, Reserva $reserva)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        try {
            $validated = $request->validate([
                'cliente' => 'sometimes|required|string|max:255',
                'telefono' => 'sometimes|required|string|max:20',
                'fecha' => 'sometimes|required|date',
                'hora_inicio' => [
                    'required',
                    'date_format:H:i',
                    function ($attr, $value, $fail) {
                        $inicio = Carbon::parse($value);
                        $horaMin = Carbon::createFromTime(16, 0);

                        if ($inicio->lt($horaMin)) {
                            $fail('La hora de inicio debe ser a partir de las 16:00.');
                        }
                    }
                ],
                'hora_fin' => [
                    'required',
                    'date_format:H:i',
                    function ($attr, $value, $fail) use ($request) {
                        $inicio = Carbon::parse($request->hora_inicio);
                        $fin = Carbon::parse($value);

                        $horaMin = Carbon::createFromTime(16, 0);
                        $horaMax = Carbon::createFromTime(0, 0)->addDay();

                        if (!($request->hora_inicio == '23:00' && $value == '00:00')) {
                            if ($inicio->lt($horaMin) || $fin->gt($horaMax)) {
                                $fail('Solo se permiten reservas entre las 16:00 y las 00:00.');
                            }
                            if ($fin <= $inicio) {
                                $fail('La hora de fin debe ser posterior a la hora de inicio.');
                            }
                        }
                    }
                ],
                'cancha_id' => 'sometimes|required|integer|exists:canchas,id',
                'estado' => 'sometimes|required|string|max:50|in:pendiente,aprobada,cancelada,activa',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }

        $horaInicio = Carbon::parse($request->hora_inicio);
        $horaFin = Carbon::parse($request->hora_fin);
        if ($horaFin <= $horaInicio) {
            $horaFin->addDay();
        }

        $conflicto = Reserva::where('fecha', $request->fecha ?? $reserva->fecha)
            ->where('cancha_id', $request->cancha_id ?? $reserva->cancha_id)
            ->whereIn('estado', ['pendiente', 'aprobada', 'activa'])
            ->where('id', '!=', $reserva->id)
            ->get()
            ->filter(function ($r) use ($horaInicio, $horaFin) {
                $resInicio = Carbon::parse($r->hora_inicio);
                $resFin = Carbon::parse($r->hora_fin);
                if ($resFin <= $resInicio) $resFin->addDay();

                return $horaInicio < $resFin && $horaFin > $resInicio;
            })
            ->isNotEmpty();

        if ($conflicto) {
            return response()->json(['error' => 'Ya existe una reserva en ese horario'], 409);
        }

        $reserva->update($validated);

        return response()->json([
            'message' => 'Reserva actualizada correctamente.',
            'reserva' => $reserva
        ], 200);
    }

    /**
     * @OA\Delete(
     * path="/api/reservas/{reserva}",
     * summary="Eliminar una reserva (Solo ADMIN)",
     * tags={"Reservas"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="reserva",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Reserva eliminada correctamente"),
     * @OA\Response(response=403, description="Acceso denegado. Solo administradores"),
     * @OA\Response(response=404, description="Reserva no encontrada")
     * )
     */
    public function destroy(Reserva $reserva)
    {
        $user = auth()->user();

        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }
        $reserva->delete();

        return response()->json(['message' => 'Reserva eliminada correctamente.'], 200);
    }

    public function getCanchas()
    {
        return response()->json(Cancha::all());
    }
    /**
     * @OA\Get(
     *     path="/api/horarios",
     *     summary="Obtener horarios disponibles",
     *     tags={"Reservas"},
     *     @OA\Parameter(name="fecha", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="canchaId", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de horarios disponibles"),
     *     @OA\Response(response=400, description="Fecha y cancha son requeridas")
     * )
     */

    public function getHorarios(Request $request)
    {
        $fecha = $request->query('fecha');
        $canchaId = $request->query('canchaId');

        if (!$fecha || !$canchaId) {
            return response()->json(['error' => 'Fecha y cancha son requeridas'], 400);
        }

        // Horarios posibles (16:00 a 23:00) + 00:00 como límite
        $todosHorarios = [];
        for ($h = 16; $h <= 23; $h++) {
            $todosHorarios[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }
        $todosHorarios[] = '00:00';

        // Obtener reservas activas para la cancha y fecha
        $reservas = Reserva::where('fecha', $fecha)
            ->where('cancha_id', $canchaId)
            ->whereIn('estado', ['pendiente', 'aprobada', 'activa'])
            ->get(['hora_inicio', 'hora_fin']);

        // array de horas ocupadas
        $horasOcupadas = [];
        foreach ($reservas as $reserva) {
            $inicio = Carbon::parse($reserva->hora_inicio);
            $fin = Carbon::parse($reserva->hora_fin);
            if ($fin <= $inicio) $fin->addDay();

            $hora = clone $inicio;
            while ($hora < $fin) {
                $horasOcupadas[] = $hora->format('H:00');
                $hora->addHour();
            }
        }

        // Filtrar horarios libres
        $horariosLibres = array_values(array_filter($todosHorarios, function ($h) use ($horasOcupadas) {
            return !in_array($h, $horasOcupadas);
        }));

        return response()->json($horariosLibres);
    }
    /**
     * Obtener métricas de ocupación (Solo ADMIN)
     *
     * @OA\Get(
     * path="/api/reservas/metrics",
     * summary="Obtener métricas de ocupación (Administrador)",
     * tags={"Estadísticas"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Métricas de ocupación"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Admin)")
     * )
     */

    public function getMetrics(Request $request)
{
    if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
        return response()->json(['message' => 'Acceso denegado.'], 403);
    }

    $totalCanchas = Cancha::count();
     $reservasActivas = Reserva::whereIn('estado', ['aprobada', 'activa'])->count();

    $bloquesPorDia = 8; 
    $fecha = $request->input('fecha') ?? Carbon::today();

    $reservasDelDia = Reserva::whereIn('estado', ['aprobada', 'activa'])
        ->whereDate('fecha', $fecha)
        ->get();

    $bloquesOcupados = $reservasDelDia->sum(function($reserva) {
        return Carbon::parse($reserva->hora_fin)
            ->diffInMinutes(Carbon::parse($reserva->hora_inicio)) / 60;
    });

    $totalBloques = $totalCanchas * $bloquesPorDia;
    $ocupacion = $totalBloques > 0 ? round(($bloquesOcupados / $totalBloques) * 100, 2) : 0;

    return response()->json([
        'total_canchas' => $totalCanchas,
        'reservas_del_dia' => $reservasDelDia->count(),
        'reservas_activas' => $reservasActivas,
        'ocupacion' => $totalCanchas > 0 ? round(($reservasActivas / $totalCanchas) * 100, 2) : 0,
        'bloques_ocupados' => $bloquesOcupados,
        'bloques_totales' => $totalBloques,
    ]);
}

    /**
     * Obtener reservas activas (Solo ADMIN)
     *
     * @OA\Get(
     * path="/api/reservas/activas",
     * summary="Obtener todas las reservas activas (Administrador)",
     * tags={"Reservas"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Lista de reservas activas"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Admin)")
     * )
     */
    public function getReservasActivas()
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $reservas = Reserva::with('cancha')
            ->whereIn('estado', ['activa', 'aprobada'])
            ->get()
            ->map(function ($reserva) {
                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->cliente,
                    'telefono' => $reserva->telefono, // 
                    'cancha' => $reserva->cancha->nombre,
                    'fecha' => $reserva->fecha,
                    'hora_inicio' => $reserva->hora_inicio,
                    'hora_fin' => $reserva->hora_fin,
                    'estado' => $reserva->estado
                ];
            });

        return response()->json($reservas);
    }

    /**
     * Obtener reservas activas por usuario (Usuario Autenticado)
     *
     * @OA\Get(
     * path="/api/reservations",
     * summary="Obtener reservas activas del usuario autenticado",
     * tags={"Reservas"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Reservas activas del usuario"),
     * @OA\Response(response=401, description="Usuario no autenticado")
     * )
     */
    public function getReservasActivasPorUsuario(Request $request)
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['error' => 'Usuario no autenticado.'], 401);
        }

        $reservas = Reserva::with('cancha')
            ->where('user_id', $userId)
            ->whereIn('estado', ['activa', 'aprobada', 'pendiente', 'cancelada'])
             ->orderBy('fecha', 'asc')
            ->get();

        return response()->json($reservas);
    }


    /**
     * Obtener reservas pendientes (Solo ADMIN)
     *
     * @OA\Get(
     * path="/api/reservas/pendientes",
     * summary="Obtener reservas pendientes (Administrador)",
     * tags={"Reservas"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Lista de reservas pendientes"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Admin)")
     * )
     */
    public function getReservasPendientes()
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }
        $reservas = Reserva::with('cancha')
            ->where('estado', 'pendiente')
            ->get()
            ->map(function ($reserva) {
                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->cliente,
                    'cancha' => $reserva->cancha->nombre,
                    'fecha' => $reserva->fecha,
                    'hora_inicio' => $reserva->hora_inicio,
                    'hora_fin' => $reserva->hora_fin,
                    'estado' => $reserva->estado
                ];
            });

        return response()->json($reservas);
    }

    /**
     * @OA\Get(
     *   path="/api/reservas/canceladas",
     *   summary="Obtener reservas canceladas (Solo ADMIN)",
     *   tags={"Reservas"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Lista de reservas canceladas"),
     *   @OA\Response(response=403, description="Acceso denegado (Requiere Admin)")
     * )
     */
    public function getReservasCanceladas()
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $reservas = Reserva::with('cancha')
            ->where('estado', 'cancelada')
            ->get()
            ->map(function ($reserva) {
                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->cliente,
                    'cancha' => $reserva->cancha->nombre,
                    'fecha' => $reserva->fecha,
                    'hora_inicio' => $reserva->hora_inicio,
                    'hora_fin' => $reserva->hora_fin,
                    'estado' => $reserva->estado
                ];
            });

        return response()->json($reservas);
    }
    /**
     * @OA\Put(
     * path="/api/reservas/{id}/estado",
     * summary="Actualizar estado de una reserva (Solo ADMIN)",
     * tags={"Reservas"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"estado"},
     * @OA\Property(property="estado", type="string", example="aprobada")
     * )
     * ),
     * @OA\Response(response=200, description="Estado actualizado"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Admin)"),
     * @OA\Response(response=404, description="Reserva no encontrada")
     * )
     */
    public function actualizarEstado(Request $request, $id)
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $request->validate([
            'estado' => 'required|string|in:pendiente,aprobada,cancelada,activa',
        ]);

        $reserva = Reserva::findOrFail($id);
        $reserva->estado = $request->estado;
        $reserva->save();

        //Notificación cuando la reserva cambie a Aprobada
        if ($reserva->estado === 'aprobada') {
        try {
            $reserva->load('user', 'cancha');
            Mail::to($reserva->user->email)->send(new \App\Mail\Correo_Ticket_Reserva($reserva));
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de confirmación de reserva (ID ' . $reserva->id . '): ' . $e->getMessage());
        }
    }
        //Fin notificación por Reserva.  Corrección de lugar

        return response()->json(['message' => 'Estado actualizado', 'reserva' => $reserva]);
    }

    /**
     * @OA\Get(
     * path="/api/reservas/ingresos",
     * summary="Obtener ingresos mensuales (Solo ADMIN)",
     * tags={"Estadísticas"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Ingresos mensuales calculados"),
     * @OA\Response(response=403, description="Acceso denegado (Requiere Admin)")
     * )
     */
    public function getIngresosMensuales()
    {
        if (auth()->user() && !in_array(auth()->user()->role, ['master', 'administrador'])) {
            return response()->json(['message' => 'Acceso denegado. Se requiere rol de administrador o master.'], 403);
        }

        $reservas = Reserva::with('cancha')
            ->whereMonth('fecha', Carbon::now()->month)
            ->whereYear('fecha', Carbon::now()->year)
            ->whereIn('estado', ['activa', 'aprobada'])
            ->get();

        $ingresos = 0;

        foreach ($reservas as $reserva) {
            try {
                $inicio = Carbon::parse($reserva->hora_inicio);
                $fin = Carbon::parse($reserva->hora_fin);
            } catch (\Exception $e) {
                continue;
            }

            $duracionHoras = $inicio->diffInMinutes($fin) / 60;

            $precioHora = $reserva->cancha->precio_hora ?? 0;
            $ingresos += $duracionHoras * $precioHora;
        }

        return response()->json(['ingresos' => round($ingresos, 2)]);
    }
    /**
     * @OA\Get(
     *     path="/api/disponibilidad-mes",
     *     summary="Obtener disponibilidad por día para todo el mes",
     *     tags={"Reservas"},
     *     @OA\Parameter(name="mes", in="query", required=true, description="Formato YYYY-MM", @OA\Schema(type="string")),
     *     @OA\Parameter(name="canchaId", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Estados de disponibilidad por día (libre, parcial, ocupado)"),
     *     @OA\Response(response=400, description="Mes y cancha son requeridos")
     * )
     */
    public function getDisponibilidadMes(Request $request)
    {
        $mes = $request->query('mes'); // formato YYYY-MM
        $canchaId = $request->query('canchaId');

        if (!$mes || !$canchaId) {
            return response()->json(['error' => 'Mes y cancha son requeridos'], 400);
        }

        $primerDia = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $ultimoDia = $primerDia->copy()->endOfMonth();

        $reservas = Reserva::where('cancha_id', $canchaId)
            ->whereBetween('fecha', [$primerDia->toDateString(), $ultimoDia->toDateString()])
            ->whereIn('estado', ['pendiente', 'aprobada', 'activa'])
            ->get();

        $disponibilidad = [];
        $totalHorarios = 8; // cantidad de turnos posibles por día (de 16:00 a 23:00)

        for ($dia = $primerDia->copy(); $dia <= $ultimoDia; $dia->addDay()) {
            $fecha = $dia->toDateString();
            $reservasDelDia = $reservas->where('fecha', $fecha);

            $horasOcupadas = 0;

            foreach ($reservasDelDia as $reserva) {
                $inicio = Carbon::parse($reserva->hora_inicio)->hour;
                $fin = Carbon::parse($reserva->hora_fin)->hour;
                if ($fin === 0) $fin = 24; // permitir reservas hasta medianoche
                $horasOcupadas += ($fin - $inicio);
            }

            if ($horasOcupadas === 0) {
                $estado = 'libre'; // 🟢 día completamente libre
            } elseif ($horasOcupadas >= $totalHorarios) {
                $estado = 'ocupado'; // 🔴 día completamente lleno
            } else {
                $estado = 'parcial'; // 🟠 día parcialmente ocupado
            }

            $disponibilidad[$fecha] = $estado;
        }

        return response()->json($disponibilidad);
    }

    public function getDisponibilidad()
    {

        $reservasOcupadas = Reserva::with('cancha')
            ->whereIn('estado', ['activa', 'aprobada'])
            ->get()
            ->map(function ($reserva) {
                // Solo devolvemos los campos críticos para la disponibilidad del calendario
                return [
                    'cancha_id' => $reserva->cancha_id,
                    'fecha' => $reserva->fecha,
                    'hora_inicio' => $reserva->hora_inicio,
                    'hora_fin' => $reserva->hora_fin,
                ];
            });

        return response()->json($reservasOcupadas);
    }
}

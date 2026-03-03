<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="API en Laravel",
 * description="Documentación de la API con Swagger en Laravel"
 * )
 *
 * @OA\Server(
 * url="http://127.0.0.1:8000",
 * description="Servidor local"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Usa un token JWT para autenticar"
 * )
 */

abstract class Controller
{
    //
}